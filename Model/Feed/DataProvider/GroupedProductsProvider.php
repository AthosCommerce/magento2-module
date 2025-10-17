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

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\LocalizedException;
use Magento\GroupedProduct\Model\Product\Type\Grouped as Grouped;
use Magento\Store\Model\StoreManagerInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\ChildAttributesProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\ValueProcessor;
use AthosCommerce\Feed\Model\Feed\DataProvider\Grouped\GetChildCollection;
use AthosCommerce\Feed\Model\Feed\DataProvider\Product\GetChildProductsData;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;

class GroupedProductsProvider implements DataProviderInterface
{
    /**
     * @var ChildAttributesProvider
     */
    private $childAttributesProvider;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var GetChildCollection
     */
    private $getChildCollection;
    /**
     * @var MetadataPool
     */
    private $metadataPool;
    /**
     * @var GetChildProductsData
     */
    private $getChildProductsData;
    /**
     * @var ValueProcessor
     */
    private $valueProcessor;

    /**
     * GroupedProductsProvider constructor.
     * @param ChildAttributesProvider $childAttributesProvider
     * @param StoreManagerInterface $storeManager
     * @param GetChildCollection $getChildCollection
     * @param MetadataPool $metadataPool
     * @param GetChildProductsData $getChildProductsData
     * @param ValueProcessor $valueProcessor
     */
    public function __construct(
        ChildAttributesProvider $childAttributesProvider,
        StoreManagerInterface $storeManager,
        GetChildCollection $getChildCollection,
        MetadataPool $metadataPool,
        GetChildProductsData $getChildProductsData,
        ValueProcessor $valueProcessor
    ) {
        $this->childAttributesProvider = $childAttributesProvider;
        $this->storeManager = $storeManager;
        $this->getChildCollection = $getChildCollection;
        $this->metadataPool = $metadataPool;
        $this->getChildProductsData = $getChildProductsData;
        $this->valueProcessor = $valueProcessor;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws LocalizedException
     * @throws Exception
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $groupedProducts = $this->getGroupedProducts($products);
        if (empty($groupedProducts)) {
            return $products;
        }

        $childAttributes = $this->childAttributesProvider->getAttributes($feedSpecification);
        $childAttributeCodes = array_map(function ($attribute) {
            return $attribute->getAttributeCode();
        }, $childAttributes);

        $storeId = (int) $this->storeManager->getStore($feedSpecification->getStoreCode())->getId();
        $childProductsCollection = $this->getChildCollection->execute(
            array_keys($groupedProducts),
            $childAttributeCodes,
            $storeId
        );
        $childProducts = $this->processChildProducts($childProductsCollection->getItems());
        foreach ($products as &$product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            $id = $productModel->getData($this->getLinkField());
            if (!isset($childProducts[$id])) {
                continue;
            }

            $product = array_merge(
                $product,
                $this->getChildProductsData->getProductData(
                    $product,
                    $childProducts[$id],
                    $childAttributes,
                    $feedSpecification
                )
            );
        }

        return $products;
    }

    /**
     * @param Product[] $childProducts
     * @return array
     */
    private function processChildProducts(array $childProducts) : array
    {
        $result = [];
        foreach ($childProducts as $product) {
            if ($product->getData('_linked_to_product_id')) {
                $result[$product->getData('_linked_to_product_id')][] = $product;
            }
        }

        return $result;
    }

    /**
     * @param array $products
     * @return array
     * @throws Exception
     */
    private function getGroupedProducts(array $products) : array
    {
        $groupedProducts = [];
        foreach ($products as $product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            if (Grouped::TYPE_CODE === $productModel->getTypeId()) {
                $id = $productModel->getData($this->getLinkField());
                if ($id) {
                    $groupedProducts[$id] = $productModel;
                }
            }
        }

        return $groupedProducts;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function getLinkField() : string
    {
        return $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
    }

    /**
     *
     */
    public function reset(): void
    {
        $this->childAttributesProvider->reset();
        $this->valueProcessor->reset();
    }

    /**
     *
     */
    public function resetAfterFetchItems(): void
    {
        // do nothing
    }
}
