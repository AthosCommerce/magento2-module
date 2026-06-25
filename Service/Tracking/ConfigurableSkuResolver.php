<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Service\Tracking;

use Magento\Catalog\Model\Product\Type;
use Magento\Sales\Api\Data\OrderItemInterface;

class ConfigurableSkuResolver implements SkuResolverInterface
{
    /**
     * @param $item
     * @return string|null
     */
    public function getProductSku($item): ?string
    {
        if (!$item instanceof OrderItemInterface) {
            return (string)$item->getSku();
        }
        $childOrderItem = $this->getChildOrderItem($item);
        if ($childOrderItem) {
            return (string)$childOrderItem->getSku();
        }
        return (string)$item->getSku();
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
