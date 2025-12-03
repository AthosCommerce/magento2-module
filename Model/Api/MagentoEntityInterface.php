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

interface MagentoEntityInterface
{
    /**
     * @return int
     */
    public function getEntityId(): int;

    /**
     * @return int|null
     */
    public function getEntityParentId(): ?int;

    /**
     * @return string|null
     */
    public function getEntitySubtype(): ?string;

    /**
     * @return string
     */
    public function getSiteId(): string;

    /**
     * @return bool
     */
    public function isIndexable(): bool;

    /**
     * @param bool $isIndexable
     *
     * @return void
     */
    public function setIsIndexable(bool $isIndexable): void;
}
