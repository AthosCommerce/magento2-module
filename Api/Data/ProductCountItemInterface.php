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

interface ProductCountItemInterface
{
    /**
     * @return int
     */
    public function getSiteId(): string;

    /**
     * @param string $siteId
     * @return $this
     */
    public function setSiteId(string $siteId): ProductCountItemInterface;

    /**
     * @return int
     */
    public function getTotalProductCount(): int;

    /**
     * @param int $count
     * @return ProductCountItemInterface
     */
    public function setTotalProductCount(int $count): ProductCountItemInterface;

    /**
     * @return int
     */
    public function getDeleteProductCount(): int;

    /**
     * @param int $count
     * @return ProductCountItemInterface
     */
    public function setDeleteProductCount(int $count): ProductCountItemInterface;

    /**
     * @return int
     */
    public function getUpsertProductCount(): int;

    /**
     * @param int $count
     * @return ProductCountItemInterface
     */
    public function setUpsertProductCount(int $count): ProductCountItemInterface;
}
