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

namespace AthosCommerce\Feed\Service\Provider\Api;

use AthosCommerce\Feed\Model\ResourceModel\IndexingEntity\Collection;
use AthosCommerce\Feed\Api\Data\IndexingEntityInterface;
use AthosCommerce\Feed\Model\Source\Actions;
use Magento\Framework\DataObject;

interface IndexingEntityProviderInterface
{
    /**
     *  Note: as sortOrder is required for pagination to work correctly,
     *   if $pageSize is provided then $sorting is ignored and collection is sorted by IndexingEntity::ENTITY_ID
     *
     * @param string|null $entityType
     * @param string[]|null $siteIds
     * @param int[]|null $entityIds
     * @param string|null $nextAction
     * @param bool|null $isIndexable
     * @param array<string, string>|null $sorting [SortOrder::DIRECTION => SortOrder::SORT_ASC, SortOrder::FIELD => '']
     * @param int|null $pageSize
     * @param int|null $startFrom
     * @param string[]|null $entitySubtypes
     *
     * @return array<IndexingEntityInterface&DataObject>
     */
    public function get(
        ?string $entityType = null,
        ?array $siteIds = [],
        ?array $entityIds = [],
        ?string $nextAction = null,
        ?bool $isIndexable = null,
        ?array $sorting = [],
        ?int $pageSize = null,
        ?int $startFrom = 1,
        ?array $entitySubtypes = [],
    ): array;

    /**
     * @param string|null $entityType
     * @param string|null $siteIds
     * @param int[][]|null $pairs
     *
     * @return Collection
     */
    public function getForTargetParentPairs(
        ?string $entityType = null,
        ?string $siteIds = null,
        ?array $pairs = [],
    ): Collection;

    /**
     * @param string|null $entityType
     * @param string|null $siteIds
     * @param string|null $nextAction
     * @param bool|null $isIndexable
     *
     * @return int
     */
    public function count(
        ?string $entityType = null,
        ?string $siteIds = null,
        ?string $nextAction = null,
        ?bool $isIndexable = null,
    ): int;

    /**
     * @param string $siteIds
     *
     * @return string[]
     */
    public function getTypes(string $siteIds): array;
}
