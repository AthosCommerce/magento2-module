<?php

declare(strict_types=1);

namespace Magento\Framework;

if (!class_exists(DataObjectFactory::class, false)) {
    class DataObjectFactory
    {
        public function create(array $data = [])
        {
            return new DataObject($data['data'] ?? []);
        }
    }
}
