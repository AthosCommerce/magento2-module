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

namespace AthosCommerce\Feed\Api\Data;

interface EntityTrackingItemInterface
{
    /**
     * @return int
     */
    public function getEntityId(): int;

    /**
     * @param int $entityId
     *
     * @return $this
     */
    public function setEntityId(int $entityId): self;

    /**
     * @return int
     */
    public function getTargetId(): int;

    /**
     * @param int $targetId
     *
     * @return $this
     */
    public function setTargetId(int $targetId): self;

    /**
     * @return string
     */
    public function getEntityType(): string;

    /**
     * @param string $entityType
     *
     * @return $this
     */
    public function setEntityType(string $entityType): self;

    /**
     * @return string|null
     */
    public function getTargetEntitySubtype(): ?string;

    /**
     * @param string|null $targetEntitySubtype
     *
     * @return $this
     */
    public function setTargetEntitySubtype(?string $targetEntitySubtype): self;

    /**
     * @return int|null
     */
    public function getTargetParentId(): ?int;

    /**
     * @param int|null $targetParentId
     *
     * @return $this
     */
    public function setTargetParentId(?int $targetParentId): self;

    /**
     * @return string
     */
    public function getSiteId(): string;

    /**
     * @param string $siteId
     *
     * @return $this
     */
    public function setSiteId(string $siteId): self;

    /**
     * @return string
     */
    public function getStatus(): string;

    /**
     * @param string $status
     *
     * @return $this
     */
    public function setStatus(string $status): self;

    /**
     * @return string
     */
    public function getNextAction(): string;

    /**
     * @param string $nextAction
     *
     * @return $this
     */
    public function setNextAction(string $nextAction): self;

    /**
     * @return string
     */
    public function getLastAction(): string;

    /**
     * @param string $lastAction
     *
     * @return $this
     */
    public function setLastAction(string $lastAction): self;

    /**
     * @return string|null
     */
    public function getLastActionTimestamp(): ?string;

    /**
     * @param string|null $lastActionTimestamp
     *
     * @return $this
     */
    public function setLastActionTimestamp(?string $lastActionTimestamp): self;

    /**
     * @return string|null
     */
    public function getLockTimestamp(): ?string;

    /**
     * @param string|null $lockTimestamp
     *
     * @return $this
     */
    public function setLockTimestamp(?string $lockTimestamp): self;

    /**
     * @return bool
     */
    public function getIsIndexable(): bool;

    /**
     * @param bool $isIndexable
     *
     * @return $this
     */
    public function setIsIndexable(bool $isIndexable): self;

    /**
     * @return string|null
     */
    public function getLastApiResponse(): ?string;

    /**
     * @param string|null $lastApiResponse
     *
     * @return $this
     */
    public function setLastApiResponse(?string $lastApiResponse): self;

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * @param string|null $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt(?string $updatedAt): self;

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * @param string|null $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(?string $createdAt): self;
}
