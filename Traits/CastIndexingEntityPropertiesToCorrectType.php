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

namespace AthosCommerce\Feed\Traits;

use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Api\Data\IndexingEntityInterface;
use AthosCommerce\Feed\Model\Source\Actions;

trait CastIndexingEntityPropertiesToCorrectType
{
    /**
     * Set correct types on object fields.
     * Protects against direct use of resourceModel to load data or using getData after loading via repository
     * i.e. $object = $repo->getById(); $object->getData('target_id'); which would return a string, rather than an int.
     *
     * @param IndexingEntityInterface $object
     *
     * @return void
     */
    private function castPropertiesToCorrectType(IndexingEntityInterface $object): void
    {
        /** @var IndexingEntityInterface $object */
        $object->setId((int)$object->getId());
        $object->setTargetEntityType($object->getTargetEntityType());
        $object->setTargetId($object->getTargetId());
        $object->setTargetParentId($object->getTargetParentId());
        $object->setSiteId($object->getSiteId());
        $nextAction = $object->getData(IndexingEntity::NEXT_ACTION);
        if (null === $nextAction) {
            $nextAction = '';
        }
        $object->setNextAction($nextAction);
        
        $lockTimestamp = $object->getLockTimestamp();
        $object->setLockTimestamp(
            $lockTimestamp
                ?: null,
        );

        $lastAction = $object->getData(IndexingEntity::LAST_ACTION);
        if (null === $lastAction) {
            $lastAction = '';
        }
        $object->setLastAction($lastAction);
        $lastActionTimestamp = $object->getLastActionTimestamp();
        $object->setLastActionTimestamp(
            $lastActionTimestamp
                ?: null,
        );
        $object->setIsIndexable($object->getIsIndexable());
    }
}
