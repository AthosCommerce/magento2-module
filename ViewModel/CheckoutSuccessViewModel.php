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

namespace AthosCommerce\Feed\ViewModel;

use AthosCommerce\Feed\Service\Config;
use AthosCommerce\Feed\Service\Tracking\CompositeOrderItemPriceResolver;
use AthosCommerce\Feed\Service\Tracking\CompositeSkuResolver;
use Magento\Checkout\Model\Session;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

class CheckoutSuccessViewModel implements ArgumentInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var CompositeOrderItemPriceResolver
     */
    private $priceResolver;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var CompositeSkuResolver
     */
    private $skuResolver;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param Config $config
     * @param CompositeOrderItemPriceResolver $priceResolver
     * @param Session $checkoutSession
     * @param CompositeSkuResolver $skuResolver
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Config                          $config,
        CompositeOrderItemPriceResolver $priceResolver,
        Session                         $checkoutSession,
        CompositeSkuResolver            $skuResolver,
        SerializerInterface             $serializer
    )
    {
        $this->config = $config;
        $this->priceResolver = $priceResolver;
        $this->checkoutSession = $checkoutSession;
        $this->skuResolver = $skuResolver;
        $this->serializer = $serializer;
    }

    /**
     * @return OrderInterface|null
     */
    private function getOrder(): ?OrderInterface
    {
        $order = $this->checkoutSession->getLastRealOrder();

        if (!$order || $order->getId() === null) {
            return null;
        }

        return $order;
    }

    /**
     * @return OrderItemInterface[]
     */
    private function getOrderItems(): array
    {
        $order = $this->getOrder();

        if (!$order) {
            return [];
        }

        $items = $order->getAllVisibleItems();

        return is_array($items) ? $items : [];
    }

    /**
     * @return array
     */
    public function getProducts(): array
    {
        $products = [];

        foreach ($this->getOrderItems() as $orderItem) {
            $parentItem = $orderItem->getParentItem();

            $products[] = [
                'uid' => $orderItem->getProductId() !== null ? (string)$orderItem->getProductId() : null,
                'sku' => $this->skuResolver->getProductSku($orderItem),
                'parentId' => $parentItem && $parentItem->getProductId() !== null
                    ? (string)$parentItem->getProductId()
                    : null,
                'qty' => $this->getProductQuantity($orderItem),
                'price' => (float)$this->priceResolver->getProductPrice($orderItem),
            ];
        }

        return $products;
    }

    /**
     * @param OrderItemInterface $orderItem
     * @return int
     */
    private function getProductQuantity(OrderItemInterface $orderItem): int
    {
        return (int)$orderItem->getQtyOrdered();
    }

    /**
     * @return string
     */
    public function getSuccessPageConfig(): string
    {
        $order = $this->getOrder();

        if (!$order) {
            return $this->serializer->serialize([]);
        }

        $billingAddress = $order->getBillingAddress();

        $config = [
            'orderId' => (string)$order->getId(),
            'totals' => [
                'transactionTotal' => (float)$order->getGrandTotal(),
                'total' => (float)$order->getSubtotal(),
                'city' => $billingAddress ? (string)$billingAddress->getCity() : '',
                'state' => $billingAddress ? (string)$billingAddress->getRegion() : '',
                'country' => $billingAddress ? (string)$billingAddress->getCountryId() : '',
            ],
            'products' => $this->getProducts(),
        ];

        return $this->serializer->serialize($config);
    }
}
