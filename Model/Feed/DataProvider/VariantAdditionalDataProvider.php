<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\EntityManager\MetadataPool;

class VariantAdditionalDataProvider implements DataProviderInterface
{
    public const FIELD_KEY = '__variant_additional_data';
    private const MAX_ADDITIONAL_FIELDS = 5;
    private const DEFAULT_VARIANT_LIMIT = 200;

    /**
     * @var ParentVariantResolver
     */
    private $parentVariantResolver;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param ParentVariantResolver $parentVariantResolver
     * @param MetadataPool $metadataPool
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        ParentVariantResolver $parentVariantResolver,
        MetadataPool $metadataPool,
        AthosCommerceLogger $logger
    ) {
        $this->parentVariantResolver = $parentVariantResolver;
        $this->metadataPool = $metadataPool;
        $this->logger = $logger;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws \Exception
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        if ($this->shouldSkip($feedSpecification)) {
            return $products;
        }

        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        $additionalFields = $this->getAdditionalFields($feedSpecification);
        $variantLimit = $this->getVariantLimit($feedSpecification);

        $this->logger->info('[VariantAdditionalDataProvider] Started', [
            'method' => __METHOD__,
            'linkField' => $linkField,
            'additionalFields' => $additionalFields,
            'variantLimit' => $variantLimit,
        ]);

        foreach ($products as &$product) {
            $productModel = $product['product_model'] ?? null;
            if (!$productModel instanceof Product) {
                continue;
            }

            $variantAdditionalData = $this->buildProductVariantAdditionalData(
                $product,
                $productModel,
                $linkField,
                $additionalFields,
                $variantLimit
            );

            if ($variantAdditionalData !== []) {
                $product[self::FIELD_KEY] = $variantAdditionalData;
            }
        }
        unset($product);

        $this->logger->info('[VariantAdditionalDataProvider] Completed', ['method' => __METHOD__]);

        return $products;
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     * @return bool
     */
    private function shouldSkip(FeedSpecificationInterface $feedSpecification): bool
    {
        return in_array(self::FIELD_KEY, $feedSpecification->getIgnoreFields(), true);
    }

    /**
     * @param array $row
     * @param Product $productModel
     * @param string $linkField
     * @param array $additionalFields
     * @param int $variantLimit
     * @return array
     */
    private function buildProductVariantAdditionalData(
        array $row,
        Product $productModel,
        string $linkField,
        array $additionalFields,
        int $variantLimit
    ): array {
        try {
            $parentProduct = $this->resolveParentProduct($row, $productModel);

            if (!$parentProduct instanceof Product || !$this->isSupportedParentType($parentProduct)) {
                return [];
            }

            return $this->buildVariantRows($parentProduct, $linkField, $additionalFields, $variantLimit);
        } catch (\Throwable $exception) {
            $this->logger->error('[VariantAdditionalDataProvider] Failed to build variant additional data', [
                'method' => __METHOD__,
                'message' => $exception->getMessage(),
                'productId' => (int)$productModel->getId(),
                'sku' => (string)$productModel->getSku(),
            ]);

            return [];
        }
    }

    /**
     * @param array $row
     * @param Product $productModel
     * @return Product|null
     */
    private function resolveParentProduct(array $row, Product $productModel): ?Product
    {
        return $this->isSupportedParentType($productModel)
            ? $productModel
            : $this->parentVariantResolver->resolveParentProductForRow($row, $productModel);
    }

    /**
     * @param Product $product
     * @return bool
     */
    private function isSupportedParentType(Product $product): bool
    {
        return in_array(
            $product->getTypeId(),
            [Constant::CONFIGURABLE_TYPE, Constant::GROUPED_TYPE],
            true
        );
    }

    /**
     * @param Product $parentProduct
     * @param string $linkField
     * @param array $additionalFields
     * @param int $variantLimit
     * @return array
     */
    private function buildVariantRows(
        Product $parentProduct,
        string $linkField,
        array $additionalFields,
        int $variantLimit
    ): array {
        $result = [];

        $childProducts = $this->parentVariantResolver->getChildProducts($parentProduct);
        $childProducts = array_slice($childProducts, 0, $variantLimit);

        foreach ($childProducts as $childProduct) {
            if ($childProduct instanceof Product) {
                $result[] = $this->buildChildRow($childProduct, $parentProduct, $linkField, $additionalFields);
            }
        }

        return $result;
    }

    /**
     * @param Product $childProduct
     * @param Product $parentProduct
     * @param string $linkField
     * @param array $additionalFields
     * @return array
     */
    private function buildChildRow(
        Product $childProduct,
        Product $parentProduct,
        string $linkField,
        array $additionalFields
    ): array {
        $options = $this->getOptions($childProduct, $parentProduct);

        $row = [
            'variant_id' => $this->getVariantId($childProduct, $linkField),
            'options'    => $options === [] ? '{}' : $options,
            'available'  => (bool)$childProduct->isAvailable(),
        ];

        foreach ($additionalFields as $field) {
            if (!array_key_exists($field, $row)) {
                $row[$field] = $this->getAdditionalFieldValue($childProduct, $field);
            }
        }

        return $row;
    }

    /**
     * @param Product $childProduct
     * @param string $linkField
     * @return string
     */
    private function getVariantId(Product $childProduct, string $linkField): string
    {
        $value = $childProduct->getData($linkField);
        return (string)($value ?: $childProduct->getId());
    }

    /**
     * @param Product $childProduct
     * @param Product $parentProduct
     * @return array
     */
    private function getOptions(Product $childProduct, Product $parentProduct): array
    {
        if ($parentProduct->getTypeId() === Constant::GROUPED_TYPE) {
            return [];
        }

        $result = [];
        $variantOptions = $this->parentVariantResolver->getVariantOptions($parentProduct, $childProduct);

        foreach ($variantOptions as $code => $optionData) {
            $result[$code] = $optionData['value'] ?? null;
        }

        return $result;
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     */
    private function getAdditionalFields(FeedSpecificationInterface $feedSpecification): array
    {
        $reservedFields = ['variant_id', 'options', 'available'];
        $fields = $feedSpecification->getVariantAdditionalFields();

        // Using PHP 7.4 arrow function for a clean filter
        $validFields = array_filter(
            array_unique($fields),
            fn($field) => is_string($field)
                && trim($field) !== ''
                && !in_array(trim($field), $reservedFields, true)
        );

        return array_slice(array_values($validFields), 0, self::MAX_ADDITIONAL_FIELDS);
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     * @return int
     */
    private function getVariantLimit(FeedSpecificationInterface $feedSpecification): int
    {
        $limit = (int)$feedSpecification->getVariantAdditionalDataLimit();
        return $limit > 0 ? $limit : self::DEFAULT_VARIANT_LIMIT;
    }

    private function getAdditionalFieldValue(Product $childProduct, string $field)
    {
        $value = $childProduct->getData($field);

        if (is_scalar($value) || $value === null || is_array($value)) {
            return $value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string)$value;
        }

        return null;
    }

    public function reset(): void {

    }

    public function resetAfterFetchItems(): void {

    }
}
