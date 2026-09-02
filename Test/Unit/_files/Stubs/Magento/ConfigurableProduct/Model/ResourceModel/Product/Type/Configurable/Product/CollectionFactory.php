<?php

declare(strict_types=1);

namespace Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product;

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
