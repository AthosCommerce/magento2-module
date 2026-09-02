<?php

declare(strict_types=1);

namespace Magento\Review\Model\ResourceModel\Review\Summary;

if (!class_exists(CollectionFactory::class, false)) {
    class CollectionFactory
    {
        private $collection;

        public function __construct($collection = null)
        {
            $this->collection = $collection;
        }

        public function create()
        {
            return $this->collection;
        }
    }
}
