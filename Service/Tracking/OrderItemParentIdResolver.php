<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Service\Tracking;

use Magento\Sales\Api\Data\OrderItemInterface;

class OrderItemParentIdResolver implements OrderItemParentIdResolverInterface
{
    /**
     * @param OrderItemInterface $orderItem
     * @return string|null
     */
    public function resolve(OrderItemInterface $orderItem): ?string
    {
        $parentItem = $orderItem->getParentItem();

        return $parentItem && $parentItem->getProductId() !== null
            ? (string)$parentItem->getProductId()
            : null;
    }
}
