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

use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentRelationsContext;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use Exception;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Framework\Exception\LocalizedException;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\AttributesProviderInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\ValueProcessor;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use AthosCommerce\Feed\Model\Feed\SystemFieldsList;

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
     * @var ParentRelationsContext
     */
    private $parentRelationsContext;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * AttributesProvider constructor.
     *
     * @param SystemFieldsList $systemFieldsList
     * @param ValueProcessor $valueProcessor
     * @param AttributesProviderInterface $attributesProvider
     * @param ParentRelationsContext $parentRelationsContext
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        SystemFieldsList            $systemFieldsList,
        ValueProcessor              $valueProcessor,
        AttributesProviderInterface $attributesProvider,
        ParentRelationsContext      $parentRelationsContext,
        AthosCommerceLogger         $logger
    )
    {
        $this->systemFieldsList = $systemFieldsList;
        $this->valueProcessor = $valueProcessor;
        $this->attributesProvider = $attributesProvider;
        $this->parentRelationsContext = $parentRelationsContext;
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
            if (!$productModel) {
                continue;
            }
            $product = array_merge(
                $product,
                $this->getProductData($productModel, $feedSpecification)
            );
        }

        return $products;
    }

    /**
     * @return string[]
     */
    private function getPriceRelatedAttributes(): array
    {
        return ['sku', 'price', 'final_price', 'tier_price', 'cost', 'special_price'];
    }

    /**
     * @param Product $product
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     * @throws LocalizedException
     * @throws Exception
     */
    private function getProductData(Product $product, FeedSpecificationInterface $feedSpecification): array
    {
        $productData = $product->getData();
        $productKeys = array_keys($productData);
        $productId = (int)$product->getData('entity_id');
        $this->logger->debug(
            '[Attributes]Processing product attributes',
            [
                'productId' => $productId,
                'productKeys' => $productKeys
            ]
        );
        $result = [];
        foreach ($productData as $attributeKey => $fieldValue) {
            /*
            For some reason the system fields does not show up
            in the attribute list resulting in missing data.
            To avoid the issue, we will include these in the
            result without any additional processing
            */
            if (!isset($this->attributes[$attributeKey])) {
                $result[$attributeKey] = $fieldValue;
                continue;
            }
            /** @var Attribute $attribute */
            $attribute = $this->attributes[$attributeKey];

            $parentProduct = $this->parentRelationsContext->getParentsByChildId($productId);
            if ($parentProduct instanceof Product) {
                $parentValue = $parentProduct->getData($attributeKey);
                //TODO: check for true/false or 0/1
                if (!in_array($attributeKey, $this->getPriceRelatedAttributes())
                    && $parentValue !== null
                    && $parentValue !== ''
                ) {
                    $fieldValue = $parentValue;
                }
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
                    '[Attributes]Failed processing product attribute', [
                        'product_id' => $productId,
                        'attribute' => $attributeKey,
                        'exception' => $e
                    ]
                );
                continue;
            }
        }
        $this->logger->debug(
            '[Attributes]Attributes processed',
            [
                'productId' => $productId,
                'fieldValue' => $fieldValue
            ]
        );

        return $result;
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     */
    private function loadAttributes(FeedSpecificationInterface $feedSpecification): void
    {
        if (is_null($this->attributes)) {
            $attributes = $this->attributesProvider->getAttributes($feedSpecification);
            $systemAttributes = $this->systemFieldsList->get();
            foreach ($attributes as $attribute) {
                if (!in_array($attribute->getAttributeCode(), $systemAttributes)) {
                    $this->attributes[$attribute->getAttributeCode()] = $attribute;
                }
            }
        }
    }

    /**
     *
     */
    public function reset(): void
    {
        $this->attributes = null;
        $this->valueProcessor->reset();
        $this->attributesProvider->reset();
    }

    /**
     *
     */
    public function resetAfterFetchItems(): void
    {
        // do nothing
    }
}
