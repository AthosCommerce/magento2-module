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
namespace AthosCommerce\Feed\Model\Api;

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
     * @param string $storeCode
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getAllCategories(
        bool $activeOnly = true,
        string $delimiter = '>',
        int $currentPage = 1,
        int $pageSize = 15,
        string $storeCode = ''
    ) {
        return [
            'data' => [
                'categories' => $this->categoryHelper->getList(
                    $activeOnly,
                    $delimiter,
                    $currentPage,
                    $pageSize,
                    $storeCode
                ),
                'total' => $this->categoryHelper->getTotalCategories(),
                'currentPage' => $currentPage,
                'pageSize' => $pageSize,
            ],
        ];
    }
}
