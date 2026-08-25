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

namespace AthosCommerce\Feed\Api\LiveIndexing;

interface DeleteEntityHandlerInterface
{
    /**
     * Process a single entity id.
     *
     * For child products (configurable/grouped children) the id is a composite
     * string in the format "parentId_childId" matching the entity_id sent during
     * UPSERT.  For standalone products it is the plain string representation of
     * the Magento entity id.
     *
     * @param string $entityId
     *
     * @return bool True if success
     */
    public function process(string $entityId): bool;
}
