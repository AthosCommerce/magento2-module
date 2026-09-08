<?php

declare(strict_types=1);

namespace Magento\Framework\Api;

if (!class_exists(SortOrderBuilderFactory::class, false)) {
    class SortOrderBuilderFactory
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
