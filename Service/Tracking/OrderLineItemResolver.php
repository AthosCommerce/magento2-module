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
