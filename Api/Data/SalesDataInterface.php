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

interface SalesDataInterface
{
    /**
     * @return string
     */
    public function getOrderId(): string;

    /**
     * @return string
     */
    public function getCustomerId(): string;

    /**
     * @return string
     */
    public function getProductId(): string;

    /**
     * @return string
     */
    public function getQuantity(): string;

    /**
     * @return string
     * @return null
     */
    public function getPrice(): string;

    /**
     * @return string
     * @return null
     */
    public function getCreatedAt(): string;

    /**
     * @param string $value
     * @return null
     */
    public function setOrderId(string $value);

    /**
     * @param string $value
     * @return null
     */
    public function setCustomerId(string $value);

    /**
     * @param string $value
     * @return null
     */
    public function setProductId(string $value);

    /**
     * @param string $value
     * @return null
     */
    public function setQuantity(string $value);

    /**
     * @param string $value
     * @return null
     */
    public function setPrice(string $value);

    /**
     * @param string $value
     * @return null
     */
    public function setCreatedAt(string $value);
}
