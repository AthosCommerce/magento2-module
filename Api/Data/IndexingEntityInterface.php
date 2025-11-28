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

namespace AthosCommerce\Feed\Api\Data;

use AthosCommerce\Feed\Model\Source\Actions;

interface IndexingEntityInterface
{

    /**
     * @return int
     */
    public function getId(); //phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint

    /**
     * @param int $value
     *
     * @return void
     */
    public function setId($value
    ); //phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint, SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint, Generic.Files.LineLength.TooLong

    /**
     * @return string
     */
    public function getTargetEntityType(): string;

    /**
     * @param string $entityType
     *
     * @return void
     */
    public function setTargetEntityType(string $entityType): void;

    /**
     * @return string|null
     */
    public function getTargetEntitySubtype(): ?string;

    /**
     * @param string|null $entitySubtype
     *
     * @return void
     */
    public function setTargetEntitySubtype(?string $entitySubtype): void;

    /**
     * @return int
     */
    public function getTargetId(): int;

    /**
     * @param int $targetId
     *
     * @return void
     */
    public function setTargetId(int $targetId): void;

    /**
     * @return int|null
     */
    public function getTargetParentId(): ?int;

    /**
     * @param int|null $targetParentId
     *
     * @return void
     */
    public function setTargetParentId(?int $targetParentId = null): void;

    /**
     * @return string
     */
    public function getSiteId(): string;

    /**
     * @param string $siteId
     *
     * @return void
     */
    public function setSiteId(string $siteId): void;

    /**
     * @return \AthosCommerce\Feed\Model\Source\Actions
     */
    public function getNextAction(): Actions;

    /**
     * @param \AthosCommerce\Feed\Model\Source\Actions $nextAction
     *
     * @return void
     */
    public function setNextAction(Actions $nextAction): void;

    /**
     * @return string|null
     */
    public function getLockTimestamp(): ?string;

    /**
     * @param string|null $lockTimestamp
     *
     * @return void
     */
    public function setLockTimestamp(?string $lockTimestamp = null): void;

    /**
     * @return \AthosCommerce\Feed\Model\Source\Actions
     */
    public function getLastAction(): Actions;

    /**
     * @param \AthosCommerce\Feed\Model\Source\Actions $lastAction
     *
     * @return void
     */
    public function setLastAction(Actions $lastAction): void;

    /**
     * @return string|null
     */
    public function getLastActionTimestamp(): ?string;

    /**
     * @param string|null $lastActionTimestamp
     *
     * @return void
     */
    public function setLastActionTimestamp(?string $lastActionTimestamp = null): void;

    /**
     * @return bool
     */
    public function getIsIndexable(): bool;

    /**
     * @param bool $isIndexable
     *
     * @return void
     */
    public function setIsIndexable(bool $isIndexable): void;

    /**
     * @param mixed[] $keys
     *
     * @return mixed[]
     */
    public function toArray(array $keys = []
    ); //phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint, Generic.Files.LineLength.TooLong
}
