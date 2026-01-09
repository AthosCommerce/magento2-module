<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Service\Tracking;

use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Class ProductSkuResolver
 *
 * This class gets SKU for product
 *
 * @package AthosCommerce\Feed\Service
 */
class ProductSkuResolver implements SkuResolverInterface
{
    /**
     * @param CartItemInterface|OrderItemInterface $product
     * @return string|null
     */
    public function getProductSku($product): ?string
    {
        return (string)$product->getProduct()->getData('sku');
    }
}
