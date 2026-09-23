<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Aws\Client;

if (!class_exists(ResponseInterfaceFactory::class, false)) {
    class ResponseInterfaceFactory
    {
        /**
         * @param array $data
         * @return null
         */
        public function create(array $data = [])
        {
            return null;
        }
    }
}
