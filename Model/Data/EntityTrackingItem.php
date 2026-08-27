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

use AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface;
use Magento\Framework\DataObject;

class EntityTrackingItem extends DataObject implements EntityTrackingItemInterface
{
    /**
     * @return int
     */
    public function getEntityId(): int
    {
        return (int)$this->getData('entity_id');
    }

    /**
     * @param int $entityId
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface
     */
    public function setEntityId(int $entityId): EntityTrackingItemInterface
    {
        return $this->setData('entity_id', $entityId);
    }

    /**
     * @return int
     */
    public function getTargetId(): int
    {
        return (int)$this->getData('target_id');
    }

    /**
     * @param int $targetId
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface
     */
    public function setTargetId(int $targetId): EntityTrackingItemInterface
    {
        return $this->setData('target_id', $targetId);
    }

    /**
     * @return string
     */
    public function getEntityType(): string
    {
        return (string)$this->getData('entity_type');
    }

    /**
     * @param string $entityType
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface
     */
    public function setEntityType(string $entityType): EntityTrackingItemInterface
    {
        return $this->setData('entity_type', $entityType);
    }

    /**
     * @return string|null
     */
    public function getTargetEntitySubtype(): ?string
    {
        $targetEntitySubtype = $this->getData('target_entity_subtype');
        return $targetEntitySubtype === null ? null : (string)$targetEntitySubtype;
    }

    /**
     * @param string|null $targetEntitySubtype
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface
     */
    public function setTargetEntitySubtype(?string $targetEntitySubtype): EntityTrackingItemInterface
    {
        return $this->setData('target_entity_subtype', $targetEntitySubtype);
    }

    /**
     * @return int|null
     */
    public function getTargetParentId(): ?int
    {
        $targetParentId = $this->getData('target_parent_id');
        return $targetParentId === null ? null : (int)$targetParentId;
    }

    /**
     * @param int|null $targetParentId
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface
     */
    public function setTargetParentId(?int $targetParentId): EntityTrackingItemInterface
    {
        return $this->setData('target_parent_id', $targetParentId);
    }

    /**
     * @return string
     */
    public function getSiteId(): string
    {
        return (string)$this->getData('site_id');
    }

    /**
     * @param string $siteId
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface
     */
    public function setSiteId(string $siteId): EntityTrackingItemInterface
    {
        return $this->setData('site_id', $siteId);
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return (string)$this->getData('status');
    }

    /**
     * @param string $status
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface
     */
    public function setStatus(string $status): EntityTrackingItemInterface
    {
        return $this->setData('status', $status);
    }

    /**
     * @return string
     */
    public function getNextAction(): string
    {
        return (string)$this->getData('next_action');
    }

    /**
     * @param string $nextAction
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface
     */
    public function setNextAction(string $nextAction): EntityTrackingItemInterface
    {
        return $this->setData('next_action', $nextAction);
    }

    /**
     * @return string
     */
    public function getLastAction(): string
    {
        return (string)$this->getData('last_action');
    }

    /**
     * @param string $lastAction
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface
     */
    public function setLastAction(string $lastAction): EntityTrackingItemInterface
    {
        return $this->setData('last_action', $lastAction);
    }

    /**
     * @return string|null
     */
    public function getLastActionTimestamp(): ?string
    {
        $lastActionTimestamp = $this->getData('last_action_timestamp');
        return $lastActionTimestamp === null ? null : (string)$lastActionTimestamp;
    }

    /**
     * @param string|null $lastActionTimestamp
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface
     */
    public function setLastActionTimestamp(?string $lastActionTimestamp): EntityTrackingItemInterface
    {
        return $this->setData('last_action_timestamp', $lastActionTimestamp);
    }

    /**
     * @return string|null
     */
    public function getLockTimestamp(): ?string
    {
        $lockTimestamp = $this->getData('lock_timestamp');
        return $lockTimestamp === null ? null : (string)$lockTimestamp;
    }

    /**
     * @param string|null $lockTimestamp
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface
     */
    public function setLockTimestamp(?string $lockTimestamp): EntityTrackingItemInterface
    {
        return $this->setData('lock_timestamp', $lockTimestamp);
    }

    /**
     * @return bool
     */
    public function getIsIndexable(): bool
    {
        return (bool)$this->getData('is_indexable');
    }

    /**
     * @param bool $isIndexable
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface
     */
    public function setIsIndexable(bool $isIndexable): EntityTrackingItemInterface
    {
        return $this->setData('is_indexable', $isIndexable);
    }

    /**
     * @return string|null
     */
    public function getLastApiResponse(): ?string
    {
        $lastApiResponse = $this->getData('last_api_response');
        return $lastApiResponse === null ? null : (string)$lastApiResponse;
    }

    /**
     * @param string|null $lastApiResponse
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface
     */
    public function setLastApiResponse(?string $lastApiResponse): EntityTrackingItemInterface
    {
        return $this->setData('last_api_response', $lastApiResponse);
    }

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        $updatedAt = $this->getData('updated_at');
        return $updatedAt === null ? null : (string)$updatedAt;
    }

    /**
     * @param string|null $updatedAt
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface
     */
    public function setUpdatedAt(?string $updatedAt): EntityTrackingItemInterface
    {
        return $this->setData('updated_at', $updatedAt);
    }

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        $createdAt = $this->getData('created_at');
        return $createdAt === null ? null : (string)$createdAt;
    }

    /**
     * @param string|null $createdAt
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface
     */
    public function setCreatedAt(?string $createdAt): EntityTrackingItemInterface
    {
        return $this->setData('created_at', $createdAt);
    }
}
