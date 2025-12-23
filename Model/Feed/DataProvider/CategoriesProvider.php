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
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Category\CollectionBuilder;
use AthosCommerce\Feed\Model\Feed\DataProvider\Category\GetCategoriesByProductIds;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableType;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class CategoriesProvider implements DataProviderInterface
{
    /**
     * @var Category[]
     */
    private $loadedCategories = [];
    /**
     * @var array
     */
    private $categoriesData = [];
    /**
     * @var CollectionBuilder
     */
    private $collectionBuilder;
    /**
     * @var GetCategoriesByProductIds
     */
    private $getCategoriesByProductIds;
    /** @var ConfigurableType */
    private $configurableType;
    /** @var AthosCommerceLogger */
    private $logger;

    /**
     * CategoriesProvider constructor.
     *
     * @param CollectionBuilder $collectionBuilder
     * @param GetCategoriesByProductIds $getCategoriesByProductIds
     * @param ConfigurableType $configurableType
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        CollectionBuilder $collectionBuilder,
        GetCategoriesByProductIds $getCategoriesByProductIds,
        ConfigurableType $configurableType,
        AthosCommerceLogger $logger
    ) {
        $this->collectionBuilder = $collectionBuilder;
        $this->getCategoriesByProductIds = $getCategoriesByProductIds;
        $this->configurableType = $configurableType;
        $this->logger = $logger;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     * @throws Exception
     */
    public function getData(
        array $products,
        FeedSpecificationInterface $feedSpecification
    ): array {
        $productIds = [];
        $parentMap = []; // simpleId → parentId

        foreach ($products as $product) {
            if (!isset($product['entity_id'])) {
                continue;
            }

            $simpleId = (int)$product['entity_id'];
            $productIds[] = $simpleId;

            $parentIds = $this->configurableType->getParentIdsByChild($simpleId);

            if (!empty($parentIds)) {
                $parentId = (int)$parentIds[0];
                $parentMap[$simpleId] = $parentId;
                $productIds[] = $parentId;
            }
        }

        $productIds = array_unique($productIds);

        if (empty($productIds)) {
            return $products;
        }

        $ignoredFields = $feedSpecification->getIgnoreFields();
        $productsCategories = $this->getCategoriesByProductIds->execute($productIds);
        $this->loadCategories($productsCategories, $feedSpecification);
        foreach ($products as &$product) {
            $entityId = $product['entity_id'] ?? null;
            if (!$entityId || !isset($productsCategories[$entityId])) {
                continue;
            }

            $categorySourceId = $parentMap[$entityId] ?? $entityId;

            if (!isset($productsCategories[$categorySourceId])) {
                continue;
            }

            $productCategories = $this->buildProductCategories($productsCategories[$entityId]);
            $parentCategories = isset($parentMap[$entityId])
                ? $this->buildProductCategories($productsCategories[$categorySourceId])
                : null;

            if (!in_array('categories', $ignoredFields)
                && isset($productCategories['categories'])
            ) {
                $product['categories'] = $productCategories['categories'];
            }

            if (!in_array('category_ids', $ignoredFields)
                && isset($productCategories['category_ids'])
            ) {
                $product['category_ids'] = $productCategories['category_ids'];
            }

            if (!in_array('category_hierarchy', $ignoredFields)
                && isset($productCategories['category_hierarchy'])
            ) {
                $product['category_hierarchy'] = $productCategories['category_hierarchy'];
            }

            if (!in_array('menu_hierarchy', $ignoredFields)
                && isset($productCategories['menu_hierarchy'])
                && $feedSpecification->getIncludeMenuCategories()
            ) {
                $product['menu_hierarchy'] = $productCategories['menu_hierarchy'];
            }

            if (!in_array('url_hierarchy', $ignoredFields)
                && isset($productCategories['url_hierarchy'])
                && $feedSpecification->getIncludeUrlHierarchy()
            ) {
                $product['url_hierarchy'] = $productCategories['url_hierarchy'];
            }

            if (!$parentCategories) {
                continue;
            }
            if (!in_array('parent_category_id', $ignoredFields)) {
                $product['parent_category_id'] = $categorySourceId;
            }
            if (!in_array('parent_categories', $ignoredFields)) {
                $product['parent_categories'] = $parentCategories['categories'] ?? [];
            }
            if (!in_array('parent_category_ids', $ignoredFields)) {
                $product['parent_category_ids'] = $parentCategories['category_ids'] ?? [];
            }
            if (!in_array('parent_category_hierarchy', $ignoredFields)) {
                $product['parent_category_hierarchy'] = $parentCategories['category_hierarchy'] ?? [];
            }
            if (!in_array('parent_menu_hierarchy', $ignoredFields)) {
                $product['parent_menu_hierarchy'] = $parentCategories['menu_hierarchy'] ?? [];
            }
            if (!in_array('parent_url_hierarchy', $ignoredFields)) {
                $product['parent_url_hierarchy'] = $parentCategories['url_hierarchy'] ?? [];
            }
        }

        return $products;
    }

    /**
     * @param array $productCategories
     *
     * @return array
     */
    private function buildProductCategories(array $productCategories): array
    {
        $categoryHierarchy = [];
        $menuHierarchy = [];
        $urlHierarchy = [];
        $ids = [];
        $categoryNames = [];
        foreach ($productCategories as $productCategory) {
            $categoryId = $productCategory['category_id'] ?? null;
            if (!$categoryId) {
                continue;
            }

            $category = $this->categoriesData[$categoryId] ?? null;
            if (!$category) {
                continue;
            }

            $ids[] = (int)$categoryId;
            $categoryNames[] = $category['name'];

            $categoryHierarchy = $this->mergeUniquePreserveOrder(
                $categoryHierarchy,
                $category['hierarchy'] ?? []
            );
            if (($category['include_menu'] ?? false)) {
                $menuHierarchy = $this->mergeUniquePreserveOrder(
                    $menuHierarchy,
                    $category['hierarchy'] ?? []
                );
            }
            if (isset($category['url_hierarchy'])) {
                $urlHierarchy = $this->mergeUniquePreserveOrder(
                    $urlHierarchy,
                    $category['url_hierarchy']
                );
            }
        }

        return [
            'categories' => array_values(array_unique($categoryNames)),
            'category_ids' => array_values(array_unique($ids)),
            'category_hierarchy' => $categoryHierarchy,
            'menu_hierarchy' => $menuHierarchy,
            'url_hierarchy' => $urlHierarchy,
        ];
    }

    /**
     * @param array $base
     * @param array $new
     *
     * @return array
     */
    private function mergeUniquePreserveOrder(array $base, array $new): array
    {
        foreach ($new as $item) {
            if (!in_array($item, $base, true)) {
                $base[] = $item;
            }
        }

        return $base;
    }

    /**
     * @param array $productsCategories
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @throws LocalizedException
     */
    private function loadCategories(
        array $productsCategories,
        FeedSpecificationInterface $feedSpecification
    ): void {
        $productsCategoryIds = [];
        foreach ($productsCategories as $categoryList) {
            $productsCategoryIds = array_merge(
                $productsCategoryIds,
                $this->getCategoryIds($categoryList)
            );
        }

        $productsCategoryIds = array_unique($productsCategoryIds);
        $loadedCategoryIds = array_keys($this->loadedCategories);
        $requiredCategoryIds = array_diff($productsCategoryIds, $loadedCategoryIds);
        if (empty($requiredCategoryIds)) {
            return;
        }

        $collection = $this->collectionBuilder->buildCollection(
            $requiredCategoryIds,
            $feedSpecification
        );
        /** @var Category[] $categories */
        $categories = $collection->getItems();

        if (empty($categories)) {
            return;
        }

        $storeCode = $feedSpecification->getStoreCode();

        foreach ($categories as $category) {
            if ($category) {
                $category->setStoreId($storeCode);

                $this->loadedCategories[$category->getEntityId()] = $category;
            }
        }

        foreach ($categories as $category) {
            $this->categoriesData[$category->getEntityId()] = $this->buildCategoryData(
                $category,
                $feedSpecification
            );
        }
    }

    /**
     * @param Category $category
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     */
    private function buildCategoryData(
        Category $category,
        FeedSpecificationInterface $feedSpecification
    ): array {
        $pathIds = $category->getPathIds();
        $hierarchySeparator = $feedSpecification->getHierarchySeparator();
        $includeUrlHierarchy = $feedSpecification->getIncludeUrlHierarchy();

        $result = [
            'name' => $category->getName(),
            'include_menu' => $category->getIncludeInMenu(),
        ];

        $includeUrlHierarchy = $feedSpecification->getIncludeUrlHierarchy();
        $categoryHierarchy = [];
        $urlHierarchy = [];
        $currentHierarchy = [];
        $hierarchySeparator = $feedSpecification->getHierarchySeparator();
        foreach ($pathIds as $pathId) {
            $pathCategory = $this->loadedCategories[$pathId] ?? null;
            if (!$pathCategory) {
                continue;
            }

            // Skip root categories
            if ($pathId <= 2) {
                continue;
            }

            $name = $pathCategory->getName();
            $currentHierarchy[] = $name;

            $levelPath = implode($hierarchySeparator, $currentHierarchy);

            if (!in_array($levelPath, $categoryHierarchy, true)) {
                $categoryHierarchy[] = $levelPath;
            }

            if ($includeUrlHierarchy) {
                $urlHierarchy[] = $levelPath . '[' . $pathCategory->getUrl() . ']';
            }
        }

        $result['hierarchy'] = array_values(array_unique($categoryHierarchy));
        if ($includeUrlHierarchy) {
            $result['url'] = $category->getUrl();
            $result['url_hierarchy'] = array_values(array_unique($urlHierarchy));
        }

        return $result;
    }

    /**
     * @param array $categoryList
     *
     * @return array
     */
    private function getCategoryIds(array $categoryList): array
    {
        $result = [];
        foreach ($categoryList as $item) {
            $categoryId = $item['category_id'] ?? null;
            $path = $item['path'] ?? null;
            if (!$categoryId) {
                continue;
            }

            $result[] = (int)$categoryId;
            if ($path) {
                $pathCategories = array_map('intval', explode('/', $path));
                $result = array_merge($result, $pathCategories);
            }
        }

        return array_unique($result);
    }

    /**
     *
     */
    public function reset(): void
    {
        $this->loadedCategories = [];
        $this->categoriesData = [];
    }

    /**
     *
     */
    public function resetAfterFetchItems(): void
    {
        // do nothing
    }
}
