<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Service\Tracking;

use Magento\Sales\Api\Data\OrderItemInterface;

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
