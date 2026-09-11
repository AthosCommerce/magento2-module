<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Api\Data;

class SalesDataInterfaceFactory
{
    /**
     * @var object|null
     */
    private $instance;

    public function __construct(?object $instance = null)
    {
        $this->instance = $instance;
    }

    public function create()
    {
        return $this->instance;
    }
}
