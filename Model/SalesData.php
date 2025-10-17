<?php

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
