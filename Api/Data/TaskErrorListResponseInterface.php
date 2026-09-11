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

declare(strict_types=1);

namespace AthosCommerce\Feed\Api\Data;

interface TaskErrorListResponseInterface
{
    /**
     * Get task error items.
     *
     * @return TaskErrorItemInterface[]
     */
    public function getItems(): array;

    /**
     * Set task error items.
     *
     * @param TaskErrorItemInterface[] $items
     * @return self
     */
    public function setItems(array $items): self;

    /**
     * Get the total number of matching errors.
     *
     * @return int
     */
    public function getTotal(): int;

    /**
     * Set the total number of matching errors.
     *
     * @param int $total
     * @return self
     */
    public function setTotal(int $total): self;

    /**
     * Get the current page number.
     *
     * @return int
     */
    public function getCurrentPage(): int;

    /**
     * Set the current page number.
     *
     * @param int $currentPage
     * @return self
     */
    public function setCurrentPage(int $currentPage): self;

    /**
     * Get the configured page size.
     *
     * @return int
     */
    public function getPageSize(): int;

    /**
     * Set the configured page size.
     *
     * @param int $pageSize
     * @return self
     */
    public function setPageSize(int $pageSize): self;
}
