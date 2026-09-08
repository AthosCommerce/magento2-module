<?php

declare(strict_types=1);

namespace Magento\Framework\Api;

if (!class_exists(SearchCriteriaBuilderFactory::class, false)) {
    class SearchCriteriaBuilderFactory
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
