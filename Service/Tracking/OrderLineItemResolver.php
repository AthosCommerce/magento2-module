<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Service\Tracking;

use Magento\Sales\Api\Data\OrderItemInterface;

class OrderLineItemResolver implements OrderLineItemResolverInterface
{
    /**
     * @var OrderItemPriceResolverInterface
     */
    private $priceResolver;

    /**
     * @var SkuResolverInterface
     */
    private $skuResolver;

    /**
     * @var OrderItemIdResolverInterface
     */
    private $itemIdResolver;

    /**
     * @var OrderItemParentIdResolverInterface
     */
    private $parentIdResolver;

    /**
     * @param OrderItemPriceResolverInterface $priceResolver
     * @param SkuResolverInterface $skuResolver
     * @param OrderItemIdResolverInterface $itemIdResolver
     * @param OrderItemParentIdResolverInterface $parentIdResolver
     */
    public function __construct(
        OrderItemPriceResolverInterface $priceResolver,
        SkuResolverInterface $skuResolver,
        OrderItemIdResolverInterface $itemIdResolver,
        OrderItemParentIdResolverInterface $parentIdResolver
    ) {
        $this->priceResolver = $priceResolver;
        $this->skuResolver = $skuResolver;
        $this->itemIdResolver = $itemIdResolver;
        $this->parentIdResolver = $parentIdResolver;
    }

    /**
     * @param OrderItemInterface $orderItem
     * @return array<string, mixed>|null
     */
    public function resolve(OrderItemInterface $orderItem): ?array
    {
        $uid = $this->itemIdResolver->resolve($orderItem);

        if ($uid === null) {
            return null;
        }

        return [
            'uid' => $uid,
            'sku' => $this->skuResolver->getProductSku($orderItem),
            'parentId' => $this->parentIdResolver->resolve($orderItem),
            'qty' => (int)$orderItem->getQtyOrdered(),
            'price' => (float)$this->priceResolver->getProductPrice($orderItem),
        ];
    }
}
