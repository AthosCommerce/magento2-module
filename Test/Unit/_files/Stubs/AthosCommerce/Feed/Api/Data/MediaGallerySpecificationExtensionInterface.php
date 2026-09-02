<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Api\Data;

if (!interface_exists(MediaGallerySpecificationExtensionInterface::class, false)) {
    interface MediaGallerySpecificationExtensionInterface extends \Magento\Framework\Api\ExtensionAttributesInterface
    {
    }
}
