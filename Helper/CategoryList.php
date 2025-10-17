<?php
/**
 * Helper to generate category data.
 *
 * This file is part of AthosCommerce/Feed.
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace AthosCommerce\Feed\Helper;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Model\Category as MagentoCategory;

class CategoryList extends AbstractHelper
{
    private $categoryCollectionFactory;

    /**
     * @var array<int, MagentoCategory>
     */
    private $allCategoriesById = [];

    private $total = null;

    /**
     * @param Context $context
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        Context $context,
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        parent::__construct($context);
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @param bool $activeOnly
     * @param string $delimiter
     * @param int $currentPage
     * @param int $pageSize
     *
     * @return array
     * @throws LocalizedException
     */
    public function getList(
        bool $activeOnly = true,
        string $delimiter = '>',
        int $currentPage = 1,
        int $pageSize = 15
    ): array {
        $categories = [];

        // Load all categories at once with only needed attributes
        $categoryCollection = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('*');

        foreach ($categoryCollection as $category) {
            //active only category and root category as well
            if ($activeOnly && !$category->getIsActive() && $category->getParentId() > 0) {
                continue;
            }

            // considering active categories only
            $this->allCategoriesById[$category->getId()] = $category;
            /** @var $category MagentoCategory */
            $categories[] = [
                'ID' => $category->getId(),
                'Name' => $category->getName(),
                'PageLink' => $category->getUrl(),
                'ImageLink' => (string)$category->getImageUrl(),
                'ParentId' => $category->getParentId(),
                'DisplayName' => $category->getName(),
                'FullHierarchy' => $this->getFullCategoryHierarchy($category, $delimiter),
                'NumProducts' => $category->getProductCount(),
                'IsActive' => (int)$category->getIsActive(),
            ];
        }
        $this->total = count($categories);
        $offset = ($currentPage - 1) * $pageSize;

        return array_slice($categories, $offset, $pageSize);
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
            return trim($id) !== '';
        });

        if (empty($pathIds)) {
            return '';
        }

        $categoryHierarchy = [];

        foreach ($pathIds as $pathId) {
            if (!$pathId) {
                continue; // Skip if pathId is empty
            }
            if (isset($this->allCategoriesById[$pathId])) {
                $categoryHierarchy[] = $this->allCategoriesById[$pathId]->getName();
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
}
