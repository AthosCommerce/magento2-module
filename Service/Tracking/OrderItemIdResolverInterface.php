<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Service\Tracking;

use Magento\Sales\Api\Data\OrderItemInterface;

interface OrderItemIdResolverInterface
{
    /**
     * Resolve the public item identifier.
     *
     * @param OrderItemInterface $orderItem
     * @return string|null
     */
    public function resolve(OrderItemInterface $orderItem): ?string;
}
