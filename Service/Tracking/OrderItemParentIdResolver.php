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
use Magento\GroupedProduct\Model\Product\Type\Grouped;
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
        if ($parentItem && $parentItem->getProductId()) {
            return (string)$parentItem->getProductId();
        }

        $childOrderItem = $this->getChildOrderItem($orderItem);
        if ($childOrderItem) {
            return (string)$childOrderItem->getProductId();
        }

        $groupedParentId = $this->getGroupedParentIdFromOrderItem($orderItem);
        if ($groupedParentId !== null && $groupedParentId !== '') {
            return (string)$groupedParentId;
        }
        return (string)$orderItem->getProductId();
    }


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

    /**
     * Resolve grouped parent product id from order item product options.
     *
     * Expected order item product options shape commonly includes:
     * [
     *   'info_buyRequest' => [
     *     'super_product_config' => [
     *       'product_type' => 'grouped',
     *       'product_id' => '19'
     *     ]
     *   ]
     * ]
     *
     * or:
     * [
     *   'super_product_config' => [
     *     'product_type' => 'grouped',
     *     'product_id' => '19'
     *   ]
     * ]
     *
     * @param OrderItemInterface $orderItem
     * @return string|null
     */
    private function getGroupedParentIdFromOrderItem(OrderItemInterface $orderItem): ?string
    {
        $productOptions = $orderItem->getProductOptions();
        $productConfig = null;

        if (!is_array($productOptions)) {
            return null;
        }

        $productConfig = $productOptions['super_product_config'] ?? null;

        if (!is_array($productConfig) && isset($productOptions['info_buyRequest']) && is_array($productOptions['info_buyRequest'])) {
            $productConfig = $productOptions['info_buyRequest']['super_product_config'] ?? null;
        }

        if (!is_array($productConfig)) {
            return null;
        }

        if (
            !empty($productConfig['product_type']) &&
            $productConfig['product_type'] === Grouped::TYPE_CODE &&
            !empty($productConfig['product_id'])
        ) {
            return (string)$productConfig['product_id'];
        }

        return null;
    }
}
