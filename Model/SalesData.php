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

use AthosCommerce\Feed\Api\Data\SalesDataInterface;

class SalesData implements SalesDataInterface
{
    private $order_id;
    private $customer_id;
    private $product_id;
    private $quantity;
    private $price;
    private $createdAt;

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->order_id;
    }

    /**
     * @return string
     */
    public function getCustomerId(): string
    {
        return $this->customer_id;
    }

    /**
     * @return string
     */
    public function getProductId(): string
    {
        return $this->product_id;
    }

    /**
     * @return string
     */
    public function getQuantity(): string
    {
        return $this->quantity;
    }

    /**
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * @return string
     */
    public function getPrice(): string
    {
        return $this->price;
    }

    /**
     * @param string $value
     */
    public function setOrderId(string $value)
    {
        $this->order_id = $value;
    }

    /**
     * @param string $value
     */
    public function setCustomerId(string $value)
    {
        $this->customer_id = $value;
    }

    /**
     * @param string $value
     */
    public function setProductId(string $value)
    {
        $this->product_id = $value;
    }

    /**
     * @param string $value
     */
    public function setQuantity(string $value)
    {
        $this->quantity = $value;
    }

    /**
     * @param string $value
     */
    public function setCreatedAt(string $value)
    {
        $this->createdAt = $value;
    }

    /**
     * @param string $value
     */
    public function setPrice(string $value)
    {
        $this->price = $value;
    }
}
