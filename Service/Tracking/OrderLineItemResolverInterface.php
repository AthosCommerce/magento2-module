<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Service\Tracking;

use Magento\Sales\Api\Data\OrderItemInterface;

interface OrderLineItemResolverInterface
{
    /**
     * Resolve an order item into tracking payload data.
     *
     * Return null when the item should be skipped.
     *
     * @param OrderItemInterface $orderItem
     * @return array<string, mixed>|null
     */
    public function resolve(OrderItemInterface $orderItem): ?array;
}
