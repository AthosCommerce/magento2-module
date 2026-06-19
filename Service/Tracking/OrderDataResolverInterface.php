<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Service\Tracking;

use Magento\Sales\Api\Data\OrderInterface;

interface OrderDataResolverInterface
{
    /**
     * Resolve an order into tracking payload data.
     *
     * @param OrderInterface $order
     * @return array<string, mixed>
     */
    public function resolve(OrderInterface $order): array;
}
