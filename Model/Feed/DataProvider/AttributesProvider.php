<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\AttributesProviderInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\ValueProcessor;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use AthosCommerce\Feed\Model\Feed\SystemFieldsList;
use Exception;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Framework\Exception\LocalizedException;

class AttributesProvider implements DataProviderInterface
{
    /**
     * @var ProductAttributeInterface[]|null
     */
    private $attributes = null;

    /**
     * @var SystemFieldsList
     */
    private $systemFieldsList;

    /**
     * @var ValueProcessor
     */
    private $valueProcessor;

    /**
     * @var AttributesProviderInterface
     */
    private $attributesProvider;

    /**
     * @var ParentVariantResolver
     */
    private $parentVariantResolver;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param SystemFieldsList $systemFieldsList
     * @param ValueProcessor $valueProcessor
     * @param AttributesProviderInterface $attributesProvider
     * @param ParentVariantResolver $parentVariantResolver
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        SystemFieldsList $systemFieldsList,
        ValueProcessor $valueProcessor,
        AttributesProviderInterface $attributesProvider,
        ParentVariantResolver $parentVariantResolver,
        AthosCommerceLogger $logger
    ) {
        $this->systemFieldsList = $systemFieldsList;
        $this->valueProcessor = $valueProcessor;
        $this->attributesProvider = $attributesProvider;
        $this->parentVariantResolver = $parentVariantResolver;
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
        $this->loadAttributes($feedSpecification);

        foreach ($products as &$product) {
            $productModel = $product['product_model'] ?? null;

            if (!$productModel instanceof Product) {
                continue;
            }

            $product = array_merge(
                $product,
                $this->getProductData($product, $productModel, $feedSpecification)
            );
        }
        unset($product);

        return $products;
    }

    /**
     * @param array $row
     * @param Product $product
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws LocalizedException
     * @throws Exception
     */
    private function getProductData(
        array $row,
        Product $product,
        FeedSpecificationInterface $feedSpecification
    ): array {
        $productData = $product->getData();
        $productKeys = array_keys($productData);
        $productId = (int)$product->getData('entity_id');

        $this->logger->debug(
            sprintf('[Attributes][%s]Processing started', $productId),
            [
                'productKeys' => $productKeys
            ]
        );

        $result = [];
        $parentProduct = $this->parentVariantResolver->resolveParentProductForRow($row, $product);

        foreach ($productData as $attributeKey => $fieldValue) {
            /*
             * For some reason the system fields do not show up
             * in the attribute list resulting in missing data.
             * To avoid the issue, we will include these in the
             * result without any additional processing.
             */
            if (!isset($this->attributes[$attributeKey])) {
                $result[$attributeKey] = $fieldValue;
                continue;
            }

            /** @var Attribute $attribute */
            $attribute = $this->attributes[$attributeKey];

            if ($this->shouldUseParentValue($row, $parentProduct)) {
                $parentValue = $parentProduct->getData($attributeKey);

                if ($parentValue !== null && $parentValue !== '') {
                    $fieldValue = $parentValue;
                }

                $this->logger->debug(
                    sprintf('[Attributes][%s]Fallback applied for attribute %s', $productId, $attributeKey),
                    [
                        'parentProduct' => (int)$parentProduct->getId(),
                    ]
                );
            }

            try {
                $result[$attributeKey] = $this->valueProcessor->getValue(
                    $attribute,
                    $fieldValue,
                    $product,
                    $feedSpecification
                );
            } catch (\Throwable $e) {
                $this->logger->error(
                    sprintf('[Attributes][%s]Failed processing attribute: %s', $productId, $attributeKey),
                    [
                        'value' => $fieldValue,
                        'exception' => $e->getTraceAsString()
                    ]
                );
                continue;
            }

            if ($attribute->getFrontendInput() === 'textarea') {
                $fieldValue = (string)$fieldValue;
                $fieldValue = strlen($fieldValue) > 50 ? substr($fieldValue, 0, 50) . '...' : $fieldValue;
            }

            $this->logger->debug(
                sprintf('[Attributes][%s]Attribute processed: %s', $productId, $attributeKey),
                [
                    'value' => $result[$attributeKey],
                    'fieldValue' => $fieldValue,
                ]
            );
        }

        $this->logger->debug(
            sprintf('[Attributes][%s]Processing completed', $productId),
            [
                'productKeys' => $productKeys
            ]
        );

        return $result;
    }

    /**
     * @param array $row
     * @param Product|null $parentProduct
     * @return bool
     */
    private function shouldUseParentValue(array $row, ?Product $parentProduct): bool
    {
        if (!$parentProduct instanceof Product) {
            return false;
        }

        if ((int)$parentProduct->getVisibility() === Visibility::VISIBILITY_NOT_VISIBLE) {
            return false;
        }

        if (!array_key_exists(Constant::IS_BELONG_TO_PARENT_KEY, $row)) {
            return false;
        }

        return (int)$row[Constant::IS_BELONG_TO_PARENT_KEY] === 1;
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     * @return void
     */
    private function loadAttributes(FeedSpecificationInterface $feedSpecification): void
    {
        if ($this->attributes === null) {
            $attributes = $this->attributesProvider->getAttributes($feedSpecification);
            $systemAttributes = $this->systemFieldsList->get();

            foreach ($attributes as $attribute) {
                if (!in_array($attribute->getAttributeCode(), $systemAttributes, true)) {
                    $this->attributes[$attribute->getAttributeCode()] = $attribute;
                }
            }
        }
    }

    public function reset(): void
    {
        $this->attributes = null;
        $this->valueProcessor->reset();
        $this->attributesProvider->reset();
    }

    public function resetAfterFetchItems(): void
    {
        // do nothing
    }
}
