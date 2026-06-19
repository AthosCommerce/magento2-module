<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Service\Tracking;

use Magento\Sales\Api\Data\OrderItemInterface;

class OrderItemIdResolver implements OrderItemIdResolverInterface
{
    /**
     * @param OrderItemInterface $orderItem
     * @return string|null
     */
    public function resolve(OrderItemInterface $orderItem): ?string
    {
        return $orderItem->getProductId() !== null
            ? (string)$orderItem->getProductId()
            : null;
    }
}
