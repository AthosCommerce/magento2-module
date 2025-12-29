<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\ViewModel;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use AthosCommerce\Feed\Service\Config;
use AthosCommerce\Feed\Service\Tracking\CompositeOrderItemPriceResolver;
use AthosCommerce\Feed\Service\Tracking\CompositeSkuResolver;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class CheckoutViewModel
 *
 * This is view model for Checkout Success Page
 *
 * @package AthosCommerce\Feed\ViewModel
 */
class CheckoutViewModel implements ArgumentInterface
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
     * CheckoutViewModel constructor.
     *
     * @param Config $getAthoscommerceSiteId
     * @param CompositeOrderItemPriceResolver $priceResolver
     * @param Session $checkoutSession
     * @param CompositeSkuResolver $skuResolver
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Config $config,
        CompositeOrderItemPriceResolver $priceResolver,
        Session $checkoutSession,
        CompositeSkuResolver $skuResolver,
        SerializerInterface $serializer
    ) {
        $this->config = $config;
        $this->priceResolver = $priceResolver;
        $this->checkoutSession = $checkoutSession;
        $this->skuResolver = $skuResolver;
        $this->serializer = $serializer;
    }

    /**
     * @return array|null
     */
    private function getOrderItems(): ?array
    {
        return $this->checkoutSession->getLastRealOrder()->getAllVisibleItems();
    }

    /**
     * @return string|null
     */
    public function getAthoscommerceSiteId(): ?string
    {
        return $this->config->getSiteId();
    }

    /**
     * @return string|null
     */
    public function getProducts(): ?string
    {
        $orderItems = $this->getOrderItems();
        foreach ($orderItems as $orderItem) {
            if (!is_null($orderItem->getParentItem())) {
                continue;
            }
            $products[] = [
                'price' => $this->priceResolver->getProductPrice($orderItem),
                'sku' => $this->skuResolver->getProductSku($orderItem),
                'qty' => $this->getProductQuantity($orderItem),
            ];
        }

        return $this->serializer->serialize($products);
    }

    /**
     * @param OrderItemInterface $orderItem
     *
     * @return int|null
     */
    private function getProductQuantity(OrderItemInterface $orderItem): ?int
    {
        return (int)$orderItem->getQtyOrdered();
    }

    /**
     * @return int|null
     */
    public function getOrderId(): ?int
    {
        $order = $this->checkoutSession->getLastRealOrder();

        if ($order) {
            return (int)$order->getDataUsingMethod('id');
        }

        return null;
    }
}
