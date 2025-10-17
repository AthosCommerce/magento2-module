<?php

namespace AthosCommerce\Feed\Api\Data;

interface CustomersInterface
{
    /**
     * @return \AthosCommerce\Feed\Api\Data\CustomersDataInterface[]
     */
    public function getCustomers(): array;

    /**
     * @param $value \AthosCommerce\Feed\Api\Data\CustomersDataInterface[]
     * @return null
     */
    public function setCustomers(array $value);
}
