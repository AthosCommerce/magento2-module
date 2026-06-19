<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Service\Tracking;

use Magento\Catalog\Model\Product\Type;
use Magento\Sales\Api\Data\OrderItemInterface;

class CompositeOrderLineItemResolver implements OrderLineItemResolverInterface
{
    /**
     * @var OrderLineItemResolverInterface[]
     */
    private $orderLineItemResolversPool;

    /**
     * @var OrderLineItemResolverInterface
     */
    private $defaultResolver;

    /**
     * @param OrderLineItemResolverInterface[] $orderLineItemResolversPool
     * @param OrderLineItemResolverInterface $defaultResolver
     */
    public function __construct(
        array $orderLineItemResolversPool,
        OrderLineItemResolverInterface $defaultResolver
    ) {
        $this->orderLineItemResolversPool = $orderLineItemResolversPool;
        $this->defaultResolver = $defaultResolver;
    }

    /**
     * @param OrderItemInterface $orderItem
     * @return array<string, mixed>|null
     */
    public function resolve(OrderItemInterface $orderItem): ?array
    {
        $productType = (string)$orderItem->getProductType();

        $resolver = $this->orderLineItemResolversPool[$productType] ?? $this->defaultResolver;

        return $resolver->resolve($orderItem);
    }
}
