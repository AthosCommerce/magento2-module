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

interface EntityTrackingListResponseInterface
{
    /**
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface[]
     */
    public function getItems(): array;

    /**
     * @param \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface[] $items
     *
     * @return $this
     */
    public function setItems(array $items): self;

    /**
     * @return int
     */
    public function getTotal(): int;

    /**
     * @param int $total
     *
     * @return $this
     */
    public function setTotal(int $total): self;

    /**
     * @return int
     */
    public function getCurrentPage(): int;

    /**
     * @param int $currentPage
     *
     * @return $this
     */
    public function setCurrentPage(int $currentPage): self;

    /**
     * @return int
     */
    public function getPageSize(): int;

    /**
     * @param int $pageSize
     *
     * @return $this
     */
    public function setPageSize(int $pageSize): self;
}
