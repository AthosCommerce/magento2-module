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

namespace AthosCommerce\Feed\Model\Update;

interface EntityInterface
{
    /**
     * @return string
     */
    public function getEntityType(): string;

    /**
     * @param string $entityType
     *
     * @return void
     */
    public function setEntityType(string $entityType): void;

    /**
     * @return int[]
     */
    public function getEntityIds(): array;

    /**
     * @param int[] $entityIds
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setEntityIds(array $entityIds): void;
}
