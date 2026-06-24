<?php

namespace AthosCommerce\Feed\Service\Tracking;

use Magento\Catalog\Model\Product\Type;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

class ConfigurableSkuResolver implements SkuResolverInterface
{
    /**
     * @param CartItemInterface|OrderItemInterface $item
     * @return string|null
     */
    public function getProductSku($item): ?string
    {
        $childOrderItem = $this->getChildOrderItem($item);
        if ($childOrderItem) {
            return (string)$childOrderItem->getSku();
        }
        return (string)$item->getProduct()->getData('sku');
    }


    /**
     * @param OrderItemInterface $orderItem
     * @return array|\Magento\Sales\Model\Order\Item|null
     */
    private function getChildOrderItem(OrderItemInterface $orderItem)
    {
        $order = $orderItem->getOrder();
        if (!$order) {
            return null;
        }
        $allItems = $order->getAllItems();
        $simpleItems = array_filter($allItems, static function (OrderItemInterface $item) use ($orderItem) {
            return $item->getProductType() === Type::TYPE_SIMPLE
                && $item->getParentItemId() === $orderItem->getId();
        });
        $keys = array_keys($simpleItems);

        return isset($keys[0], $simpleItems[$keys[0]]) ? $simpleItems[$keys[0]] : null;
    }
}
