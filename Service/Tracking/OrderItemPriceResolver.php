<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Service\Tracking;

use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Class OrderItemPrice
 *
 * This class gets price for order item (checkout success page)
 *
 * @package AthosCommerce\Feed\Service
 */
class OrderItemPriceResolver implements OrderItemPriceResolverInterface
{
    /**
     * @param OrderItemInterface $product
     * @return float|null
     */
    public function getProductPrice(OrderItemInterface $product): ?float
    {
        return (float)$product->getPrice();
    }
}
