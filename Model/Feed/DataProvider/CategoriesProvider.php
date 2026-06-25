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

use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use Exception;
use Magento\Catalog\Model\Category;
use Magento\Framework\Exception\LocalizedException;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Category\CollectionBuilder;
use AthosCommerce\Feed\Model\Feed\DataProvider\Category\GetCategoriesByProductIds;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableType;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;
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
    /** @var GroupedType */
    private $groupedType;
    /** @var AthosCommerceLogger */
    private $logger;

    /**
     * CategoriesProvider constructor.
     *
     * @param CollectionBuilder $collectionBuilder
     * @param GetCategoriesByProductIds $getCategoriesByProductIds
     * @param ConfigurableType $configurableType
     * @param GroupedType $groupedType
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        CollectionBuilder         $collectionBuilder,
        GetCategoriesByProductIds $getCategoriesByProductIds,
        ConfigurableType          $configurableType,
        GroupedType               $groupedType,
        AthosCommerceLogger       $logger
    )
    {
        $this->collectionBuilder = $collectionBuilder;
        $this->getCategoriesByProductIds = $getCategoriesByProductIds;
        $this->configurableType = $configurableType;
        $this->groupedType = $groupedType;
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
        array                      $products,
        FeedSpecificationInterface $feedSpecification
    ): array
    {
        $productIds = [];
        $rowCategorySourceMap = [];

        foreach ($products as $index => $product) {
            if (!isset($product['entity_id'])) {
                continue;
            }

            $entityId = (int)$product['entity_id'];
            $productIds[] = $entityId;

            $categorySourceId = $this->resolveCategorySourceId($product);
            $rowCategorySourceMap[$index] = $categorySourceId;

            if ($categorySourceId && $categorySourceId !== $entityId) {
                $productIds[] = $categorySourceId;
            }
        }

        $productIds = array_values(array_unique($productIds));

        if (empty($productIds)) {
            return $products;
        }

        $ignoredFields = $feedSpecification->getIgnoreFields();
        $productsCategories = $this->getCategoriesByProductIds->execute($productIds);
        $this->loadCategories($productsCategories, $feedSpecification);


        foreach ($products as $index => &$product) {
            $entityId = isset($product['entity_id']) ? (int)$product['entity_id'] : 0;
            if ($entityId <= 0) {
                continue;
            }

            $categorySourceId = $rowCategorySourceMap[$index] ?? $entityId;

            if (!isset($productsCategories[$categorySourceId])) {
                continue;
            }

            $resolvedCategories = $this->buildProductCategories(
                $productsCategories[$categorySourceId]
            );

            if (!in_array('categories', $ignoredFields, true)
                && isset($resolvedCategories['categories'])
            ) {
                $product['categories'] = $resolvedCategories['categories'];
            }

            if (!in_array('category_ids', $ignoredFields, true)
                && isset($resolvedCategories['category_ids'])
            ) {
                $product['category_ids'] = $resolvedCategories['category_ids'];
            }

            if (!in_array('category_hierarchy', $ignoredFields, true)
                && isset($resolvedCategories['category_hierarchy'])
            ) {
                $product['category_hierarchy'] = $resolvedCategories['category_hierarchy'];
            }

            if (!in_array('menu_hierarchy', $ignoredFields, true)
                && isset($resolvedCategories['menu_hierarchy'])
                && $feedSpecification->getIncludeMenuCategories()
            ) {
                $product['menu_hierarchy'] = $resolvedCategories['menu_hierarchy'];
            }

            if (!in_array('url_hierarchy', $ignoredFields, true)
                && isset($resolvedCategories['url_hierarchy'])
                && $feedSpecification->getIncludeUrlHierarchy()
            ) {
                $product['url_hierarchy'] = $resolvedCategories['url_hierarchy'];
            }

            if ($categorySourceId === $entityId) {
                continue;
            }

            if (!array_key_exists(Constant::IS_BELONG_TO_PARENT_KEY, $product)
                || (int)$product[Constant::IS_BELONG_TO_PARENT_KEY] !== 1
            ) {
                continue;
            }

            if (!in_array('parent_category_id', $ignoredFields, true)) {
                $product['parent_category_id'] = $categorySourceId;
            }
            if (!in_array('parent_categories', $ignoredFields, true)) {
                $product['parent_categories'] = $resolvedCategories['categories'] ?? [];
            }
            if (!in_array('parent_category_ids', $ignoredFields, true)) {
                $product['parent_category_ids'] = $resolvedCategories['category_ids'] ?? [];
            }
            if (!in_array('parent_category_hierarchy', $ignoredFields, true)) {
                $product['parent_category_hierarchy'] = $resolvedCategories['category_hierarchy'] ?? [];
            }
            if (!in_array('parent_menu_hierarchy', $ignoredFields, true)) {
                $product['parent_menu_hierarchy'] = $resolvedCategories['menu_hierarchy'] ?? [];
            }
            if (!in_array('parent_url_hierarchy', $ignoredFields, true)) {
                $product['parent_url_hierarchy'] = $resolvedCategories['url_hierarchy'] ?? [];
            }
        }
        unset($product);

        return $products;
    }

    /**
     * @param array $product
     * @return int
     */
    private function resolveCategorySourceId(array $product): int
    {
        $entityId = isset($product['entity_id']) ? (int)$product['entity_id'] : 0;
        if ($entityId <= 0) {
            return 0;
        }

        $isBelongToParent = array_key_exists(Constant::IS_BELONG_TO_PARENT_KEY, $product)
            && (int)$product[Constant::IS_BELONG_TO_PARENT_KEY] === 1;

        if (!$isBelongToParent) {
            return $entityId;
        }

        $typeId = (string)($product['type_id'] ?? '');
        $parentTypeId = (string)($product['parent_type_id'] ?? '');
        $resolvedType = $parentTypeId ?: $typeId;

        if ($resolvedType === 'configurable') {
            $parentIds = $this->configurableType->getParentIdsByChild($entityId);
            $this->logger->debug('[CategoriesProvider]Resolved configurable parent ids', [
                'entity_id' => $entityId,
                'type_id' => $typeId,
                'parent_type_id' => $parentTypeId,
                'parent_ids' => $parentIds,
            ]);
            if (!empty($parentIds)) {
                return (int)$parentIds[0];
            }
        }

        if ($resolvedType === 'grouped') {
            $parentIds = $this->groupedType->getParentIdsByChild($entityId);
            $this->logger->debug('[CategoriesProvider]Resolved grouped parent ids', [
                'entity_id' => $entityId,
                'type_id' => $typeId,
                'parent_type_id' => $parentTypeId,
                'parent_ids' => $parentIds,
            ]);
            if (!empty($parentIds)) {
                return (int)$parentIds[0];
            }
        }

        return $entityId;
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
        array                      $productsCategories,
        FeedSpecificationInterface $feedSpecification
    ): void
    {
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
        Category                   $category,
        FeedSpecificationInterface $feedSpecification
    ): array
    {
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
