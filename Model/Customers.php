<?php

namespace AthosCommerce\Feed\Model;

use AthosCommerce\Feed\Api\Data\CustomersDataInterface;
use AthosCommerce\Feed\Api\Data\CustomersInterface;

class Customers implements CustomersInterface
{
    private $customers;

    /**
     * @return CustomersDataInterface[]
     */
    public function getCustomers(): array
    {
        return $this->customers;
    }

    /**
     * @param $value CustomersDataInterface[]
     */
    public function setCustomers(array $value)
    {
        $this->customers = $value;
    }
}
