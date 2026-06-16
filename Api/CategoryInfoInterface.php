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
namespace AthosCommerce\Feed\Api;

interface CategoryInfoInterface
{
    /**
     * Get details of all categories in the store.
     *
     * @param bool $activeOnly
     * @param string $delimiter
     * @param int $currentPage
     * @param int $pageSize
     * @param string $storeCode
     *
     * @return mixed
     */
    public function getAllCategories(
        bool $activeOnly = true,
        string $delimiter = '>',
        int $currentPage = 1,
        int $pageSize = 15,
        string $storeCode = ''
    );
}
