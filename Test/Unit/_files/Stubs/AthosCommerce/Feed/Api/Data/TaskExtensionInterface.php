<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Api\Data;

if (!interface_exists(TaskExtensionInterface::class, false)) {
    interface TaskExtensionInterface extends \Magento\Framework\Api\ExtensionAttributesInterface
    {
    }
}
