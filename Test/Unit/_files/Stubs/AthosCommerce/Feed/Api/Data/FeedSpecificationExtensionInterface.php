<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Api\Data;

if (!interface_exists(FeedSpecificationExtensionInterface::class, false)) {
    interface FeedSpecificationExtensionInterface extends \Magento\Framework\Api\ExtensionAttributesInterface
    {
    }
}
