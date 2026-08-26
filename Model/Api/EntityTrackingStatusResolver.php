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

namespace AthosCommerce\Feed\Model\Api;

use AthosCommerce\Feed\Api\Data\IndexingEntityInterface;
use AthosCommerce\Feed\Model\Source\Actions;

class EntityTrackingStatusResolver
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DELETED = 'deleted';

    /**
     * Resolve the public tracking status from indexing entity fields.
     *
     * @param \AthosCommerce\Feed\Api\Data\IndexingEntityInterface $entity
     *
     * @return string
     */
    public function resolve(IndexingEntityInterface $entity): string
    {
        if (!$entity->getIsIndexable()) {
            return self::STATUS_DELETED;
        }

        if ($entity->getLockTimestamp() !== null) {
            return self::STATUS_PROCESSING;
        }

        if ($entity->getNextAction() !== Actions::NO_ACTION) {
            return self::STATUS_PENDING;
        }

        if ($entity->getLastAction() !== Actions::NO_ACTION) {
            return self::STATUS_SUCCESS;
        }

        return self::STATUS_FAILED;
    }
}
