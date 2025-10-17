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

use Magento\Framework\Api\SearchResults;
use AthosCommerce\Feed\Api\Data\CronStatusSearchResultsInterface;

class CronStatusSearchResults extends SearchResults implements CronStatusSearchResultsInterface
{
    /**
     * Get current page
     *
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->getSearchCriteria()->getCurrentPage() ?? 1;
    }

    /**
     * Set current page
     *
     * @param int $currentPage
     * @return $this
     */
    public function setCurrentPage(int $currentPage): self
    {
        $this->getSearchCriteria()->setCurrentPage($currentPage);
        return $this;
    }

    /**
     * Get page size
     *
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->getSearchCriteria()->getPageSize() ?? 20;
    }

    /**
     * Set page size
     *
     * @param int $pageSize
     * @return $this
     */
    public function setPageSize(int $pageSize): self
    {
        $this->getSearchCriteria()->setPageSize($pageSize);
        return $this;
    }
}
