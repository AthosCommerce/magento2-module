<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Api\Data;

if (!class_exists(MediaGallerySpecificationInterfaceFactory::class, false)) {
    class MediaGallerySpecificationInterfaceFactory
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
