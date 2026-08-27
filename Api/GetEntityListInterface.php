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

namespace AthosCommerce\Feed\Api;

use AthosCommerce\Feed\Api\Data\EntityTrackingListResponseInterface;

interface GetEntityListInterface
{
    /**
     * Get tracked entities with pagination and optional filters.
     *
     * @param int $currentPage
     * @param int $pageSize
     * @param int|null $targetId
     * @param int|null $targetParentId
     * @param string|null $entityType
     * @param string|null $siteId
     * @param string|null $status
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingListResponseInterface
     */
    public function getList(
        int $currentPage = 1,
        int $pageSize = 20,
        ?int $targetId = null,
        ?int $targetParentId = null,
        ?string $entityType = null,
        ?string $siteId = null,
        ?string $status = null
    ): EntityTrackingListResponseInterface;
}
