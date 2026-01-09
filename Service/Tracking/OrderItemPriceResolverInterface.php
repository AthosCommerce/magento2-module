<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Service\Tracking;

use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Interface OrderItemPriceResolverInterface
 *
 * @package AthosCommerce\Feed\Service
 */
interface OrderItemPriceResolverInterface
{
    /**
     * @param OrderItemInterface $product
     * @return float|null
     */
    public function getProductPrice(OrderItemInterface $product): ?float;
}
