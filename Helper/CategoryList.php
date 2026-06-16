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

namespace AthosCommerce\Feed\Helper;

use Magento\Catalog\Model\Category as MagentoCategory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class CategoryList extends AbstractHelper
{
    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @var array<int, string>
     */
    private $allCategoriesById = [];

    /**
     * @var null|int
     */
    private $total = null;
    /**
     * @var int
     */
    private $currentPage = 1;

    /**
     * @var int
     */
    private $pageSize = 15;

    /**
     * @param Context $context
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        Context                   $context,
        CategoryCollectionFactory $categoryCollectionFactory,
        StoreManagerInterface     $storeManager,
        AthosCommerceLogger       $logger
    )
    {
        parent::__construct($context);
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * @param bool $activeOnly
     * @param string $delimiter
     * @param int $currentPage
     * @param int $pageSize
     * @param string $storeCode
     *
     * @return array
     * @throws LocalizedException
     */
    public function getList(
        bool   $activeOnly = true,
        string $delimiter = '>',
        int    $currentPage = 1,
        int    $pageSize = 15,
        string $storeCode = ''
    ): array
    {
        $categories = [];
        $this->currentPage = max(1, $currentPage);
        $this->pageSize = max(1, $pageSize);

        $currentPage = $this->currentPage;
        $pageSize = $this->pageSize;
        $this->allCategoriesById = [];
        $this->total = null;

        $storeId = null;
        $rootCategoryId = null;

        if ($storeCode !== '') {
            try {
                $store = $this->storeManager->getStore($storeCode);
            } catch (\Throwable $e) {
                throw new LocalizedException(__("Invalid store code \"%1\".", $storeCode), $e);
            }
            $storeId = (int)$store->getId();
            $rootCategoryId = (int)$store->getRootCategoryId();
        }

        $allCategoriesCollection = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect(['name', 'path']);

        if ($storeId !== null) {
            $allCategoriesCollection->setStoreId($storeId);
        }

        if ($activeOnly) {
            $allCategoriesCollection->addAttributeToFilter('is_active', 1);
        }

        if ($rootCategoryId !== null) {
            $allCategoriesCollection->addAttributeToFilter([
                ['attribute' => 'path', 'eq' => '1/' . $rootCategoryId],
                ['attribute' => 'path', 'like' => '1/' . $rootCategoryId . '/%']
            ]);
        }

        foreach ($allCategoriesCollection as $category) {
            /** @var MagentoCategory $category */
            $this->allCategoriesById[(int)$category->getId()] = (string)$category->getName();
        }

        $categoryCollection = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect([
                'name',
                'image',
                'is_active',
                'include_in_menu',
                'meta_title',
                'meta_keywords',
                'url_path',
                'url_key',
                'path',
                'parent_id'
            ]);

        if ($storeId !== null) {
            $categoryCollection->setStoreId($storeId);
        }

        if ($activeOnly) {
            $categoryCollection->addAttributeToFilter('is_active', 1);
        }

        if ($rootCategoryId !== null) {
            $categoryCollection->addAttributeToFilter([
                ['attribute' => 'path', 'eq' => '1/' . $rootCategoryId],
                ['attribute' => 'path', 'like' => '1/' . $rootCategoryId . '/%']
            ]);
        }
        $categoryCollection->setOrder('entity_id', 'ASC');
        $categoryCollection->setCurPage($currentPage);
        $categoryCollection->setPageSize($pageSize);

        $this->total = (int)$categoryCollection->getSize();

        $this->logger->debug(
            'CategoryEndPoint Collection Query',
            [
                'query' => $categoryCollection->getSelect()->__toString(),
                'store_code' => $storeCode,
                'store_id' => $storeId,
                'root_category_id' => $rootCategoryId,
                'current_page' => $currentPage,
                'page_size' => $pageSize
            ]
        );

        foreach ($categoryCollection as $category) {
            /** @var MagentoCategory $category */
            $categories[] = [
                'ID' => (int)$category->getId(),
                'Name' => (string)$category->getName(),
                'PageLink' => $category->getUrl(),
                'ImageLink' => (string)$category->getImageUrl(),
                'ParentId' => (int)$category->getParentId(),
                'DisplayName' => (string)$category->getName(),
                'FullHierarchy' => $this->getFullCategoryHierarchy($category, $delimiter),
                'NumProducts' => (int)$category->getProductCount(),
                'IsActive' => (int)$category->getIsActive(),
                'IncludeInMenu' => (int)$category->getIncludeInMenu(),
                'MetaTitle' => (string)$category->getMetaTitle(),
                'MetaKeywords' => (string)$category->getMetaKeywords(),
                'UrlPath' => (string)$category->getData('url_path'),
                'UrlKey' => (string)$category->getUrlKey()
            ];
        }
        $this->logger->info(
            'Category list generated',
            [
                'store_code' => $storeCode,
                'store_id' => $storeId,
                'root_category_id' => $rootCategoryId,
                'active_only' => $activeOnly,
                'current_page' => $this->currentPage,
                'page_size' => $this->pageSize,
                'total_categories' => $this->total,
                'returned_categories' => count($categories)
            ]
        );

        return $categories;
    }

    /**
     * @param MagentoCategory $category
     * @param string $delimiter
     *
     * @return string
     */
    private function getFullCategoryHierarchy(MagentoCategory $category, string $delimiter): string
    {
        $pathIds = array_filter($category->getPathIds(), function ($id) {
            return trim((string)$id) !== '';
        });

        if (empty($pathIds)) {
            return '';
        }

        $categoryHierarchy = [];

        foreach ($pathIds as $pathId) {
            $pathId = (int)$pathId;
            if (!$pathId) {
                continue;
            }

            if (isset($this->allCategoriesById[$pathId])) {
                $categoryHierarchy[] = $this->allCategoriesById[$pathId];
            }
        }

        return implode($delimiter, $categoryHierarchy);
    }

    /**
     * @return null|int
     */
    public function getTotalCategories(): ?int
    {
        return $this->total;
    }

    /**
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }
}
