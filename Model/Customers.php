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

namespace AthosCommerce\Feed\Model;

use AthosCommerce\Feed\Api\Data\CustomersDataInterface;
use AthosCommerce\Feed\Api\Data\CustomersInterface;

class Customers implements CustomersInterface
{
    private $customers;
    private $totalCount = 0;
    private $currentSize = 0;
    private $pageSize = 0;

    /**
     * @return CustomersDataInterface[]
     */
    public function getCustomers(): array
    {
        return $this->customers;
    }

    /**
     * @param $value CustomersDataInterface[]
     */
    public function setCustomers(array $value)
    {
        $this->customers = $value;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function setTotalCount(int $totalCount): void
    {
        $this->totalCount = $totalCount;
    }

    public function getCurrentSize(): int
    {
        return $this->currentSize;
    }

    public function setCurrentSize(int $currentSize): void
    {
        $this->currentSize = $currentSize;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function setPageSize(int $pageSize): void
    {
        $this->pageSize = $pageSize;
    }
}
