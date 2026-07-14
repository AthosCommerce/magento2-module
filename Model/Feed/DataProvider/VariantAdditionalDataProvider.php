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
use Magento\Framework\Exception\LocalizedException;

class VariantAdditionalDataProvider implements DataProviderInterface
{
    public const FIELD_KEY = '__variant_additional_data';
    private const MAX_ADDITIONAL_FIELDS = 5;

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
     * @throws LocalizedException
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $ignoredFields = $feedSpecification->getIgnoreFields();
        if (in_array(self::FIELD_KEY, $ignoredFields, true)) {
            return $products;
        }

        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        $additionalFields = $this->getAdditionalFields($feedSpecification);

        $this->logger->info(
            '[VariantAdditionalDataProvider] Started',
            [
                'method' => __METHOD__,
                'field' => self::FIELD_KEY,
                'linkField' => $linkField,
                'additionalFields' => $additionalFields,
            ]
        );

        foreach ($products as &$product) {
            /** @var Product|null $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel instanceof Product) {
                continue;
            }

            try {
                $parentProduct = $this->resolveParentProduct($product, $productModel);

                if (!$parentProduct instanceof Product) {
                    $product[self::FIELD_KEY] = [];
                    continue;
                }

                if (!$this->isSupportedParentType($parentProduct)) {
                    $product[self::FIELD_KEY] = [];
                    continue;
                }

                $product[self::FIELD_KEY] = $this->buildVariantAdditionalData(
                    $parentProduct,
                    $additionalFields,
                    $linkField,
                    $feedSpecification
                );
            } catch (\Throwable $exception) {
                $this->logger->error(
                    '[VariantAdditionalDataProvider] Failed to build variant additional data',
                    [
                        'method' => __METHOD__,
                        'message' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString(),
                        'productId' => (int)$productModel->getId(),
                        'sku' => (string)$productModel->getSku(),
                    ]
                );

                $product[self::FIELD_KEY] = [];
            }
        }
        unset($product);

        $this->logger->info(
            '[VariantAdditionalDataProvider] Completed',
            [
                'method' => __METHOD__,
                'field' => self::FIELD_KEY,
            ]
        );

        return $products;
    }

    /**
     * @param array $row
     * @param Product $productModel
     * @return Product|null
     */
    private function resolveParentProduct(array $row, Product $productModel): ?Product
    {
        if ($this->isSupportedParentType($productModel)) {
            return $productModel;
        }

        return $this->parentVariantResolver->resolveParentProductForRow($row, $productModel);
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
     * @param array $additionalFields
     * @param string $linkField
     * @return array
     */
    private function buildVariantAdditionalData(
        Product $parentProduct,
        array $additionalFields,
        string $linkField,
        FeedSpecificationInterface $feedSpecification
    ): array {
        $result = [];
        $childProducts = $this->parentVariantResolver->getChildProducts($parentProduct);
        $childProducts = array_slice($childProducts, 0, $this->getVariantLimit($feedSpecification));

        foreach ($childProducts as $childProduct) {
            if (!$childProduct instanceof Product) {
                continue;
            }

            $result[] = $this->buildChildRow(
                $childProduct,
                $parentProduct,
                $additionalFields,
                $linkField,
                $feedSpecification
            );
        }

        return $result;
    }

    /**
     * @param Product $childProduct
     * @param Product $parentProduct
     * @param array $additionalFields
     * @param string $linkField
     * @return array
     */
    private function buildChildRow(
        Product $childProduct,
        Product $parentProduct,
        array $additionalFields,
        string $linkField,
        FeedSpecificationInterface $feedSpecification
    ): array {
        $row = [
            'variant_id' => $this->getVariantId($childProduct, $linkField),
            'options' => $this->getOptions($childProduct, $parentProduct),
            'available' => $this->isAvailable($childProduct),
        ];

        foreach ($additionalFields as $field) {
            if (array_key_exists($field, $row)) {
                continue;
            }

            $row[$field] = $this->getAdditionalFieldValue($childProduct, $field);
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

        if ($value === null || $value === '') {
            $value = $childProduct->getId();
        }

        return (string)$value;
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

        $variantOptions = $this->parentVariantResolver->getVariantOptions($parentProduct, $childProduct);
        $result = [];

        foreach ($variantOptions as $code => $optionData) {
            $result[$code] = $optionData['value'] ?? null;
        }

        return $result;
    }

    /**
     * @param Product $childProduct
     * @return bool
     */
    private function isAvailable(Product $childProduct): bool
    {
        return (bool)$childProduct->isAvailable();
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     */
    private function getAdditionalFields(FeedSpecificationInterface $feedSpecification): array
    {
        $fields = array_values(array_unique($feedSpecification->getVariantAdditionalFields()));
        $result = [];

        foreach ($fields as $field) {
            if (!is_string($field)) {
                continue;
            }

            $field = trim($field);
            if ($field === '') {
                continue;
            }

            if (in_array($field, ['variant_id', 'options', 'available'], true)) {
                continue;
            }

            $result[] = $field;

            if (count($result) >= self::MAX_ADDITIONAL_FIELDS) {
                break;
            }
        }

        return $result;
    }

    /**
     * @param Product $childProduct
     * @param string $field
     * @return mixed
     */
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

    /**
     * @param FeedSpecificationInterface $feedSpecification
     * @return int
     */
    private function getVariantLimit(FeedSpecificationInterface $feedSpecification): int
    {
        $limit = $feedSpecification->getVariantAdditionalDataLimit();

        if ($limit === null || $limit <= 0) {
            return 200;
        }

        return $limit;
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        // do nothing
    }

    /**
     * @return void
     */
    public function resetAfterFetchItems(): void
    {
        // do nothing
    }
}
