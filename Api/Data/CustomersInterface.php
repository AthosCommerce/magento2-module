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

    /**
     * @return int
     */
    public function getTotal(): int;

    /**
     * @param int $total
     * @return void
     */
    public function setTotal(int $total): void;

    /**
     * @return int
     */
    public function getReturnedCount(): int;

    /**
     * @param int $returnedCount
     * @return void
     */
    public function setReturnedCount(int $returnedCount): void;
}
