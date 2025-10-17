<?php

namespace AthosCommerce\Feed\Model;

use Magento\Framework\Exception\LocalizedException;
use AthosCommerce\Feed\Api\CategoryInfoInterface as CategoryInfoApi;
use AthosCommerce\Feed\Helper\CategoryList;

class CategoryInfo implements CategoryInfoApi
{
    /**
     * @var CategoryList
     */
    private $categoryHelper;

    /**
     * @param CategoryList $categoryHelper
     */
    public function __construct(
        CategoryList $categoryHelper
    ) {
        $this->categoryHelper = $categoryHelper;
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
    public function getAllCategories(
        bool $activeOnly = true,
        string $delimiter = '>',
        int $currentPage = 1,
        int $pageSize = 15
    ): array {
        return [
            'data' => [
                'categories' => $this->categoryHelper->getList(
                    $activeOnly,
                    $delimiter,
                    $currentPage,
                    $pageSize
                ),
                'total' => $this->categoryHelper->getTotalCategories(),
                'currentPage' => $currentPage,
                'pageSize' => $pageSize,
            ],
        ];
    }
}
