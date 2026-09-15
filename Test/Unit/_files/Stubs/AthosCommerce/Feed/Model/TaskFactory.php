<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Model;

if (!class_exists(TaskFactory::class, false)) {
    class TaskFactory
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
