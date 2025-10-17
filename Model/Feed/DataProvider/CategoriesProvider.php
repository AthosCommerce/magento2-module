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
use Magento\Framework\Exception\LocalizedException;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Category\CollectionBuilder;
use AthosCommerce\Feed\Model\Feed\DataProvider\Category\GetCategoriesByProductIds;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;

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

    /**
     * CategoriesProvider constructor.
     * @param CollectionBuilder $collectionBuilder
     * @param GetCategoriesByProductIds $getCategoriesByProductIds
     */
    public function __construct(
        CollectionBuilder $collectionBuilder,
        GetCategoriesByProductIds $getCategoriesByProductIds
    ) {
        $this->collectionBuilder = $collectionBuilder;
        $this->getCategoriesByProductIds = $getCategoriesByProductIds;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws Exception
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $productIds = [];
        foreach ($products as $product) {
            if (isset($product['entity_id'])) {
                $productIds[] = (int) $product['entity_id'];
            }
        }

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

            $productCategories = $this->buildProductCategories($productsCategories[$entityId]);
            if (!in_array('categories', $ignoredFields) && isset($productCategories['categories'])) {
                $product['categories'] = $productCategories['categories'];
            }

            if (!in_array('category_ids', $ignoredFields) && isset($productCategories['category_ids'])) {
                $product['category_ids'] = $productCategories['category_ids'];
            }

            if (!in_array('category_hierarchy', $ignoredFields) && isset($productCategories['category_hierarchy'])) {
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
        }

        return $products;
    }

    /**
     * @param array $productCategories
     * @return array
     */
    private function buildProductCategories(array $productCategories) : array
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

            $ids[] = (int) $productCategory['category_id'];
            $categoryNames[] = $category['name'];
            $categoryHierarchy = array_merge($categoryHierarchy, $category['hierarchy']);
            if (isset($category['include_menu']) ?? $category['include_menu']) {
                $menuHierarchy = array_merge($menuHierarchy, $category['hierarchy']);
            }

            if(isset($category['url_hierarchy'])) {
                $urlHierarchy = array_merge($urlHierarchy, $category['url_hierarchy']);
            }

        }

        return [
            'categories' => $categoryNames,
            'category_ids' => $ids,
            'category_hierarchy' => $categoryHierarchy,
            'menu_hierarchy' => $menuHierarchy,
            'url_hierarchy' => $urlHierarchy
        ];
    }

    /**
     * @param array $productsCategories
     * @param FeedSpecificationInterface $feedSpecification
     * @throws LocalizedException
     */
    private function loadCategories(array $productsCategories, FeedSpecificationInterface $feedSpecification) : void
    {
        $productsCategoryIds = [];
        foreach ($productsCategories as $categoryList) {
            $productsCategoryIds = array_merge($productsCategoryIds, $this->getCategoryIds($categoryList));
        }

        $productsCategoryIds = array_unique($productsCategoryIds);
        $loadedCategoryIds = array_keys($this->loadedCategories);
        $requiredCategoryIds = array_diff($productsCategoryIds, $loadedCategoryIds);
        if (empty($requiredCategoryIds)) {
            return;
        }

        $collection = $this->collectionBuilder->buildCollection($requiredCategoryIds, $feedSpecification);
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
            $this->categoriesData[$category->getEntityId()] = $this->buildCategoryData($category, $feedSpecification);
        }
    }

    /**
     * @param Category $category
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     */
    private function buildCategoryData(Category $category, FeedSpecificationInterface $feedSpecification) : array
    {
        $pathIds = $category->getPathIds();
        $result = [
            'name' => $category->getName(),
            'include_menu' => $category->getIncludeInMenu()
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

            $name = $pathCategory->getName();
            $currentHierarchy[] = $name;
            $hierarchy = implode($hierarchySeparator, $currentHierarchy);
            $categoryHierarchy[] = $hierarchy;

            if ($includeUrlHierarchy) {
                $url = $pathCategory->getUrl();
                $urlHierarchy[] = $hierarchy . '[' . $url . ']';
            }
        }

        $result['hierarchy'] = $categoryHierarchy;

        if($includeUrlHierarchy) {
            $result['url'] = $category->getUrl();
            $result['url_hierarchy'] = $urlHierarchy;
        }

        return $result;
    }

    /**
     * @param array $categoryList
     * @return array
     */
    private function getCategoryIds(array $categoryList) : array
    {
        $result = [];
        foreach ($categoryList as $item) {
            $categoryId = $item['category_id'] ?? null;
            $path = $item['path'] ?? null;
            if (!$categoryId) {
                continue;
            }

            $result[] = (int) $categoryId;
            if ($path) {
                $pathCategories = explode('/', $path);
                $pathCategories = array_map('intval', $pathCategories);
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
