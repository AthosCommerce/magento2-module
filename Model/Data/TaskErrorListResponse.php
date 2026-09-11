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

namespace AthosCommerce\Feed\Model\Data;

use AthosCommerce\Feed\Api\Data\TaskErrorListResponseInterface;
use Magento\Framework\DataObject;

class TaskErrorListResponse extends DataObject implements TaskErrorListResponseInterface
{
    /**
     * Get task error items.
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->getData('items') ?? [];
    }

    /**
     * Set task error items.
     *
     * @param array $items
     * @return TaskErrorListResponseInterface
     */
    public function setItems(array $items): TaskErrorListResponseInterface
    {
        return $this->setData('items', $items);
    }

    /**
     * Get the total number of matching errors.
     *
     * @return int
     */
    public function getTotal(): int
    {
        return (int)$this->getData('total');
    }

    /**
     * Set the total number of matching errors.
     *
     * @param int $total
     * @return TaskErrorListResponseInterface
     */
    public function setTotal(int $total): TaskErrorListResponseInterface
    {
        return $this->setData('total', $total);
    }

    /**
     * Get the current page number.
     *
     * @return int
     */
    public function getCurrentPage(): int
    {
        return (int)$this->getData('current_page');
    }

    /**
     * Set the current page number.
     *
     * @param int $currentPage
     * @return TaskErrorListResponseInterface
     */
    public function setCurrentPage(int $currentPage): TaskErrorListResponseInterface
    {
        return $this->setData('current_page', $currentPage);
    }

    /**
     * Get the configured page size.
     *
     * @return int
     */
    public function getPageSize(): int
    {
        return (int)$this->getData('page_size');
    }

    /**
     * Set the configured page size.
     *
     * @param int $pageSize
     * @return TaskErrorListResponseInterface
     */
    public function setPageSize(int $pageSize): TaskErrorListResponseInterface
    {
        return $this->setData('page_size', $pageSize);
    }
}
