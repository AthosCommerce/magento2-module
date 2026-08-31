<?php

namespace AthosCommerce\Feed\Api\Data;

interface SalesInterface
{
    /**
     * @return \AthosCommerce\Feed\Api\Data\SalesDataInterface[]
     */
    public function getSales(): array;

    /**
     * @param $value \AthosCommerce\Feed\Api\Data\SalesDataInterface[]
     * @return null
     */
    public function setSales(array $value);

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
