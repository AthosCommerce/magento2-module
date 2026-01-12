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

namespace AthosCommerce\Feed\Api;

interface ProductInfoInterface
{
    /**
     * @param int $productId
     * @param int $storeId
     *
     * @return \AthosCommerce\Feed\Api\Data\ProductInfoResponseInterface
     */
    public function getInfo(
        int $productId,
        int $storeId = 1
    ): \AthosCommerce\Feed\Api\Data\ProductInfoResponseInterface;
}
