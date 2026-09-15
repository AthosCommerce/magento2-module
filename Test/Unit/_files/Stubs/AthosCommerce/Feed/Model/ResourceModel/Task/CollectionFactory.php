<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Model\ResourceModel\Task;

if (!class_exists(CollectionFactory::class, false)) {
    class CollectionFactory
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
