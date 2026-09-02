<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Api\Data;

if (!class_exists(EntityTrackingItemInterfaceFactory::class, false)) {
    class EntityTrackingItemInterfaceFactory
    {
        private $instance;

        public function __construct($instance = null)
        {
            $this->instance = $instance;
        }

        public function create(array $data = [])
        {
            return $this->instance;
        }
    }
}
