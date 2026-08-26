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

use AthosCommerce\Feed\Api\Data\EntityTrackingListResponseInterface;
use Magento\Framework\DataObject;

class EntityTrackingListResponse extends DataObject implements EntityTrackingListResponseInterface
{
    /**
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface[]
     */
    public function getItems(): array
    {
        return $this->getData('items') ?? [];
    }

    /**
     * @param \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface[] $items
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingListResponseInterface
     */
    public function setItems(array $items): EntityTrackingListResponseInterface
    {
        return $this->setData('items', $items);
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return (int)$this->getData('total');
    }

    /**
     * @param int $total
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingListResponseInterface
     */
    public function setTotal(int $total): EntityTrackingListResponseInterface
    {
        return $this->setData('total', $total);
    }

    /**
     * @return int
     */
    public function getCurrentPage(): int
    {
        return (int)$this->getData('current_page');
    }

    /**
     * @param int $currentPage
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingListResponseInterface
     */
    public function setCurrentPage(int $currentPage): EntityTrackingListResponseInterface
    {
        return $this->setData('current_page', $currentPage);
    }

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return (int)$this->getData('page_size');
    }

    /**
     * @param int $pageSize
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingListResponseInterface
     */
    public function setPageSize(int $pageSize): EntityTrackingListResponseInterface
    {
        return $this->setData('page_size', $pageSize);
    }
}
