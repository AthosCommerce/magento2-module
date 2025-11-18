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

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Configurable;

use Exception;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute as ConfigurableAttribute;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\LocalizedException;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\AttributesProviderInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\ChildAttributesProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\ValueProcessor;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\GetAttributesCollection;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\GetChildCollection;
use AthosCommerce\Feed\Model\Feed\DataProvider\Product\ChildStorage;
use AthosCommerce\Feed\Model\Feed\DataProvider\Product\GetChildProductsData;

class DataProvider
{
    /**
     * @var GetAttributesCollection
     */
    private $getAttributesCollection;

    /**
     * @var GetChildCollection
     */
    private $getChildCollection;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var ChildAttributesProvider
     */
    private $childAttributesProvider;

    /**
     * @var GetChildProductsData
     */
    private $getChildProductsData;

    /**
     * @var ValueProcessor
     */
    private $valueProcessor;

    /**
     * @var ChildStorage
     */
    private $childStorage;
    /**
     * @var AttributesProviderInterface[]
     */
    private $attributesProvider;

    /**
     * @var array|null
     */
    private $configurableAttributes = null;

    /**
     * @var ProductAttributeInterface[]
     */
    private $attributes = null;

    /**
     * ConfigurableProductsProvider constructor.
     * @param GetAttributesCollection $getAttributesCollection
     * @param GetChildCollection $getChildCollection
     * @param MetadataPool $metadataPool
     * @param ChildAttributesProvider $childAttributesProvider
     * @param GetChildProductsData $getChildProductsData
     * @param ValueProcessor $valueProcessor
     * @param ChildStorage $childStorage
     * @param array $attributesProvider
     */
    public function __construct(
        GetAttributesCollection $getAttributesCollection,
        GetChildCollection $getChildCollection,
        MetadataPool $metadataPool,
        ChildAttributesProvider $childAttributesProvider,
        GetChildProductsData $getChildProductsData,
        ValueProcessor $valueProcessor,
        ChildStorage $childStorage,
        array $attributesProvider = []
    ) {
        $this->getAttributesCollection = $getAttributesCollection;
        $this->getChildCollection = $getChildCollection;
        $this->metadataPool = $metadataPool;
        $this->childAttributesProvider = $childAttributesProvider;
        $this->getChildProductsData = $getChildProductsData;
        $this->valueProcessor = $valueProcessor;
        $this->childStorage = $childStorage;
        $this->attributesProvider = $attributesProvider;
    }

    /**
     * @param int $productId
     * @return array|null
     */
    public function getById(int $productId): ?array
    {
        return $this->childStorage->getById($productId);
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws LocalizedException
     */
    public function getAllChildProducts(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $configurableProducts = $this->getConfigurableProducts($products);
        if (empty($configurableProducts)) {
            return $products;
        }

        if (!array_diff_key($this->childStorage->get(), $configurableProducts)
            && count($this->childStorage->get())
        ) {
            return $this->childStorage->get();
        }
        //Passing parent id array to return attribute collection
        $attributesCollection = $this->getAttributesCollection->execute($configurableProducts);
        //Child attribute codes
        $childAttributeCodes = $this->getChildAttributeCodes($attributesCollection->getItems(), $feedSpecification);
        //All child product collection based on parentIds and attributeCodes
        $childProductsCollection = $this->getChildCollection->execute($configurableProducts, $childAttributeCodes);
        //
        $childProducts = $this->processChildProducts($childProductsCollection->getItems());
        $this->childStorage->set($childProducts);

        return $childProducts;
    }

    /**
     * @param array $products
     * @return array
     * @throws Exception
     */
    public function getConfigurableProducts(array $products) : array
    {
        $configurableProducts = [];
        foreach ($products as $product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            if (Configurable::TYPE_CODE === $productModel->getTypeId()) {
                $id = $productModel->getData($this->getLinkField());
                if ($id) {
                    $configurableProducts[$id] = $productModel;
                }
            }
        }

        return $configurableProducts;
    }

    /**
     * @param array $configurableProducts
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws LocalizedException
     */
    public function getConfigurableAttributes(
        array $configurableProducts,
        FeedSpecificationInterface $feedSpecification
    ): array {
        if (is_null($this->configurableAttributes)) {
            $attributes = $this->getAttributes($configurableProducts);
            $this->configurableAttributes = $this->processAttributes($attributes, $feedSpecification);
        }

        return $this->configurableAttributes;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getLinkField() : string
    {
        return $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->childAttributesProvider->reset();
        $this->valueProcessor->reset();
        $this->resetAfterFetchItems();
    }

    /**
     * @return void
     */
    public function resetAfterFetchItems(): void
    {
        $this->childStorage->reset();
        $this->attributes = null;
        $this->configurableAttributes = null;
    }

    /**
     * @param ConfigurableAttribute[] $attributes
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws LocalizedException
     */
    private function processAttributes(array $attributes, FeedSpecificationInterface $feedSpecification) : array
    {
        $result = [];
        foreach ($attributes as $attribute) {
            $productAttribute = $attribute->getProductAttribute();
            if ($productAttribute) {
                $result[$attribute->getProductId()][$productAttribute->getAttributeId()] = $productAttribute;
            }
        }

        $specificationAttributes = $this->childAttributesProvider->getAttributes($feedSpecification);
        foreach ($result as $productId => &$productAttributes) {
            foreach ($specificationAttributes as $specificationAttribute) {
                if (!isset($productAttributes[$specificationAttribute->getAttributeId()])) {
                    $productAttributes[$specificationAttribute->getAttributeId()] = $specificationAttribute;
                }
            }
        }

        return $result;
    }

    /**
     * @param Product[] $childProducts
     * @return array
     */
    private function processChildProducts(array $childProducts) : array
    {
        $result = [];
        foreach ($childProducts as $product) {
            if ($product->getParentId()) {
                $result[$product->getParentId()][] = $product;
            }
        }

        return $result;
    }

    /**
     * @param ConfigurableAttribute[] $attributes
     * @param FeedSpecificationInterface $feedSpecification
     * @return Attribute[]
     * @throws LocalizedException
     */
    private function getChildAttributeCodes(array $attributes, FeedSpecificationInterface $feedSpecification) : array
    {
        $result = [];
        foreach ($attributes as $attribute) {
            if ($attribute->getProductAttribute()) {
                $result[] = $attribute->getProductAttribute()->getAttributeCode();
            }
        }

        $specificationAttributes = $this->childAttributesProvider->getAttributes($feedSpecification);
        foreach ($specificationAttributes as $attribute) {
            $result[] = $attribute->getAttributeCode();
        }

        foreach ($this->attributesProvider as $attributesProvider) {
            $result = array_merge($result, $attributesProvider->getAttributeCodes($feedSpecification));
        }

        return array_unique($result);
    }

    /**
     * @param array $configurableProducts
     * @return ConfigurableAttribute[]
     */
    private function getAttributes(array $configurableProducts) : array
    {
        if (is_null($this->attributes)) {
            $attributesCollection = $this->getAttributesCollection->execute($configurableProducts);

            $attributes = [];
            foreach ($attributesCollection as $attribute) {
                $attributes[] = $attribute;
            }

            $this->attributes = $attributes;
        }

        return $this->attributes;
    }
}
