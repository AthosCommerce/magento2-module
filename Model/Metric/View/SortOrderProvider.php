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

namespace AthosCommerce\Feed\Model\Metric\View;

use AthosCommerce\Feed\Model\Metric\CollectorInterface;

class SortOrderProvider
{
    /**
     * @var array
     */
    private $orders = [
        CollectorInterface::CODE_PRODUCT_FEED => [
            'name' => 10,
            '__title__' => 20,
            'timer' => 30,
            'usage' => 40,
            'usage_diff' => 50,
            'usage_real' => 60,
            'usage_real_diff' => 70,
            'peak' => 80,
            'peak_diff' => 90,
            'peak_real' => 100,
            'peak_real_diff' => 110,
            'size' => 120,
            'size_diff' => 130,
            'size_readable' => 140,
            'size_diff_readable' => 150,
            'items_data_size' => 160,
            'items_data_count' => 170,
            'date' => 1000
        ],
        CollectorInterface::CODE_TASK_EXECUTION => [
            '__title__' => 10,
            'execution_mode' => 20,
            'parallel_enabled' => 30,
            'pending_store_count' => 40,
            'spawned_store_count' => 50,
            'processed_task_count' => 60,
            'timer' => 70,
            'usage' => 80,
            'usage_diff' => 90,
            'usage_real' => 100,
            'usage_real_diff' => 110,
            'peak' => 120,
            'peak_diff' => 130,
            'peak_real' => 140,
            'peak_real_diff' => 150,
            'date' => 1000
        ]
    ];

    /**
     * SortOrderProvider constructor.
     * @param array $orders
     */
    public function __construct(
        array $orders = []
    ) {
        $this->orders = array_replace_recursive($this->orders, $orders);
    }

    /**
     * @param string $code
     * @return array
     */
    public function getSortOrder(string $code) : array
    {
        return $this->orders[$code] ?? [];
    }
}
