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

use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

class OrderDataResolver implements OrderDataResolverInterface
{
    /**
     * @var OrderLineItemResolverInterface
     */
    private $orderLineItemResolver;

    /**
     * @param OrderLineItemResolverInterface $orderLineItemResolver
     */
    public function __construct(
        OrderLineItemResolverInterface $orderLineItemResolver
    )
    {
        $this->orderLineItemResolver = $orderLineItemResolver;
    }

    /**
     * @param OrderInterface $order
     * @return array<string, mixed>
     */
    public function resolve(OrderInterface $order): array
    {
        $products = [];

        foreach ($this->getVisibleItems($order) as $orderItem) {
            $resolvedItem = $this->orderLineItemResolver->resolve($orderItem);

            if ($resolvedItem === null) {
                continue;
            }

            $products[] = $resolvedItem;
        }

        return [
            'orderId' => $this->getOrderIdentifier($order),
            'subTotal' => $this->getOrderSubTotal($order),
            'total' => $this->getOrderGrandTotal($order),
            'vat' => $this->getOrderVatAmount($order),
            'city' => $this->getOrderCity($order),
            'state' => $this->getOrderState($order),
            'country' => $this->getOrderCountry($order),
            'currency' => $this->getOrderCurrency($order),
            'products' => $products,
        ];
    }

    /**
     * @param OrderInterface $order
     * @return OrderItemInterface[]
     */
    protected function getVisibleItems(OrderInterface $order): array
    {
        $items = $order->getAllVisibleItems();

        return is_array($items) ? $items : [];
    }

    /**
     * @param OrderInterface $order
     * @return string
     */
    protected function getOrderIdentifier(OrderInterface $order): string
    {
        return $order->getId() !== null ? (string)$order->getId() : '';
    }

    /**
     * @param OrderInterface $order
     * @return float
     */
    protected function getOrderSubTotal(OrderInterface $order): float
    {
        return (float)$order->getSubtotal();
    }

    /**
     * @param OrderInterface $order
     * @return float
     */
    protected function getOrderGrandTotal(OrderInterface $order): float
    {
        return (float)$order->getGrandTotal();
    }

    /**
     * @param OrderInterface $order
     * @return float
     */
    protected function getOrderVatAmount(OrderInterface $order): float
    {
        return (float)$order->getTaxAmount();
    }

    /**
     * @param OrderInterface $order
     * @return string
     */
    protected function getOrderCurrency(OrderInterface $order): string
    {
        return (string)$order->getOrderCurrencyCode();
    }

    /**
     * @param OrderInterface $order
     * @return string
     */
    protected function getOrderCity(OrderInterface $order): string
    {
        $address = $this->getOrderAddress($order);

        return $address ? (string)$address->getCity() : '';
    }

    /**
     * @param OrderInterface $order
     * @return string
     */
    protected function getOrderState(OrderInterface $order): string
    {
        $address = $this->getOrderAddress($order);

        return $address ? (string)$address->getRegion() : '';
    }

    /**
     * @param OrderInterface $order
     * @return string
     */
    protected function getOrderCountry(OrderInterface $order): string
    {
        $address = $this->getOrderAddress($order);

        return $address ? (string)$address->getCountryId() : '';
    }

    /**
     * Prefer shipping address and fall back to billing address.
     *
     * @param OrderInterface $order
     * @return OrderAddressInterface|null
     */
    protected function getOrderAddress(OrderInterface $order): ?OrderAddressInterface
    {
        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress instanceof OrderAddressInterface) {
            return $shippingAddress;
        }

        $billingAddress = $order->getBillingAddress();
        if ($billingAddress instanceof OrderAddressInterface) {
            return $billingAddress;
        }

        return null;
    }
}
