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
}
