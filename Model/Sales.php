<?php

namespace AthosCommerce\Feed\Model;

use AthosCommerce\Feed\Api\Data\SalesInterface;

class Sales implements SalesInterface
{
    private $sales;

    /**
     * @return SalesDataInterface[]
     */
    public function getSales(): array
    {
        return $this->sales;
    }

    /**
     * @param $value SalesDataInterface[]
     */
    public function setSales(array $value)
    {
        $this->sales = $value;
    }
}
