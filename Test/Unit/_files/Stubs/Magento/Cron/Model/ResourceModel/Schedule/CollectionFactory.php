<?php

declare(strict_types=1);

namespace Magento\Cron\Model\ResourceModel\Schedule;

if (!class_exists(CollectionFactory::class, false)) {
    class CollectionFactory
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
         * @return mixed
         */
        public function create()
        {
            return $this->instance;
        }
    }
}
