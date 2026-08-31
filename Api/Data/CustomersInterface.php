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
    public function getTotalCount(): int;

    /**
     * @param int $totalCount
     * @return void
     */
    public function setTotalCount(int $totalCount): void;

    /**
     * @return int
     */
    public function getCurrentSize(): int;

    /**
     * @param int $currentSize
     * @return void
     */
    public function setCurrentSize(int $currentSize): void;

    /**
     * @return int
     */
    public function getPageSize(): int;

    /**
     * @param int $pageSize
     * @return void
     */
    public function setPageSize(int $pageSize): void;
}
