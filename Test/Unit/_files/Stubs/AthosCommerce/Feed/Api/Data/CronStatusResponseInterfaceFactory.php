<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Api\Data;

if (!class_exists(CronStatusResponseInterfaceFactory::class, false)) {
    class CronStatusResponseInterfaceFactory
    {
        /**
         * @var mixed
         */
        private $instance;

        /**
         * @param mixed $instance
         */
        public function __construct($instance = null)
        {
            $this->instance = $instance;
        }

        /**
         * @param array $data
         *
         * @return mixed
         */
        public function create(array $data = [])
        {
            return $this->instance;
        }
    }
}
