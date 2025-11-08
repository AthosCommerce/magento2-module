<?php

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Option;

class Visibility
{
    /**
     * @return string
     */
    public function getVisibilityTextValue(int $visibilityId): string
    {
        $options = \Magento\Catalog\Model\Product\Visibility::getOptionArray();

        return isset($options[$visibilityId])
            ? (string)__($options[$visibilityId])
            : (string)__('Unknown');
    }
}
