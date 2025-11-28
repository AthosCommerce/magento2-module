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

use AthosCommerce\Feed\Api\Data\IndexingEntityInterface;
use AthosCommerce\Feed\Model\ResourceModel\IndexingEntity as IndexingEntityResourceModel;
use AthosCommerce\Feed\Model\Source\Actions;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Framework\Model\AbstractModel;

class IndexingEntity extends AbstractExtensibleModel implements IndexingEntityInterface
{
    const ENTITY_ID = 'entity_id';
    const TARGET_ENTITY_TYPE = 'target_entity_type';
    const TARGET_ENTITY_SUBTYPE = 'target_entity_subtype';
    const TARGET_ID = 'target_id';
    const TARGET_PARENT_ID = 'target_parent_id';
    const SITE_ID = 'site_id';
    const NEXT_ACTION = 'next_action';
    const LOCK_TIMESTAMP = 'lock_timestamp';
    const LAST_ACTION = 'last_action';
    const LAST_ACTION_TIMESTAMP = 'last_action_timestamp';
    const IS_INDEXABLE = 'is_indexable';

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _construct(): void
    {
        $this->_init(
            IndexingEntityResourceModel::class,
        );
    }

    /**
     * @return string
     */
    public function getTargetEntityType(): string
    {
        return (string)$this->getData(static::TARGET_ENTITY_TYPE);
    }

    /**
     * @param string $entityType
     *
     * @return void
     */
    public function setTargetEntityType(string $entityType): void
    {
        $this->setData(static::TARGET_ENTITY_TYPE, $entityType);
    }

    /**
     * @return string|null
     */
    public function getTargetEntitySubtype(): ?string
    {
        $subType = $this->getData(static::TARGET_ENTITY_SUBTYPE);

        return $subType
            ? (string)$subType
            : null;
    }

    /**
     * @param string|null $entitySubtype
     *
     * @return void
     */
    public function setTargetEntitySubtype(?string $entitySubtype): void
    {
        $this->setData(static::TARGET_ENTITY_SUBTYPE, $entitySubtype);
    }

    /**
     * @return int
     */
    public function getTargetId(): int
    {
        return (int)$this->getData(static::TARGET_ID);
    }

    /**
     * @param int $targetId
     *
     * @return void
     */
    public function setTargetId(int $targetId): void
    {
        $this->setData(static::TARGET_ID, $targetId);
    }

    /**
     * @return int|null
     */
    public function getTargetParentId(): ?int
    {
        $id = $this->getData(static::TARGET_PARENT_ID);

        return $id
            ? (int)$id
            : null;
    }

    /**
     * @param int|null $targetParentId
     *
     * @return void
     */
    public function setTargetParentId(?int $targetParentId = null): void
    {
        $this->setData(static::TARGET_PARENT_ID, $targetParentId);
    }

    /**
     * @return string
     */
    public function getSiteId(): string
    {
        return (string)$this->getData(static::SITE_ID);
    }

    /**
     * @param string $siteId
     *
     * @return void
     */
    public function setSiteId(string $siteId): void
    {
        $this->setData(static::SITE_ID, $siteId);
    }

    /**
     * @return string
     */
    public function getNextAction(): string
    {
        return $this->getData(static::NEXT_ACTION);
    }

    /**
     * @param string $nextAction
     *
     * @return void
     */
    public function setNextAction(string $nextAction): void
    {
        $this->setData(static::NEXT_ACTION, $nextAction);
    }

    /**
     * @return string|null
     */
    public function getLockTimestamp(): ?string
    {
        $timestamp = $this->getData(static::LOCK_TIMESTAMP);

        return $timestamp
            ? (string)$timestamp
            : null;
    }

    /**
     * @param string|null $lockTimestamp
     *
     * @return void
     */
    public function setLockTimestamp(?string $lockTimestamp = null): void
    {
        $this->setData(static::LOCK_TIMESTAMP, $lockTimestamp);
    }

    /**
     * @return string
     */
    public function getLastAction(): string
    {
        return $this->getData(static::LAST_ACTION);
    }

    /**
     * @param string $lastAction
     *
     * @return void
     */
    public function setLastAction(string $lastAction): void
    {
        $this->setData(static::LAST_ACTION, $lastAction);
    }

    /**
     * @return string|null
     */
    public function getLastActionTimestamp(): ?string
    {
        $timestamp = $this->getData(static::LAST_ACTION_TIMESTAMP);

        return $timestamp
            ? (string)$timestamp
            : null;
    }

    /**
     * @param string|null $lastActionTimestamp
     *
     * @return void
     */
    public function setLastActionTimestamp(?string $lastActionTimestamp = null): void
    {
        $this->setData(static::LAST_ACTION_TIMESTAMP, $lastActionTimestamp);
    }

    /**
     * @return bool
     */
    public function getIsIndexable(): bool
    {
        return (bool)$this->getData(static::IS_INDEXABLE);
    }

    /**
     * @param bool $isIndexable
     *
     * @return void
     */
    public function setIsIndexable(bool $isIndexable): void
    {
        $this->setData(static::IS_INDEXABLE, (bool)$isIndexable);
    }

    /**
     * @return $this
     */
    protected function _clearData(): self
    {
        $this->setData([]);
        $this->setOrigData();
        $this->storedData = [];

        return $this;
    }
}
