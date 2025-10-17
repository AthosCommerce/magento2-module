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

namespace AthosCommerce\Feed\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface CronStatusSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get cron status list
     *
     * @return \AthosCommerce\Feed\Api\Data\CronStatusInterface[]
     */
    public function getItems();

    /**
     * Set cron status list
     *
     * @param \AthosCommerce\Feed\Api\Data\CronStatusInterface[] $items
     *
     * @return $this
     */
    public function setItems(array $items);

    /**
     * Get current page
     *
     * @return int
     */
    public function getCurrentPage(): int;

    /**
     * Set current page
     *
     * @param int $currentPage
     *
     * @return $this
     */
    public function setCurrentPage(int $currentPage): self;

    /**
     * Get page size
     *
     * @return int
     */
    public function getPageSize(): int;

    /**
     * Set page size
     *
     * @param int $pageSize
     *
     * @return $this
     */
    public function setPageSize(int $pageSize): self;
}
