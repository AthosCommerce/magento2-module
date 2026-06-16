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

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Service\Config;
use AthosCommerce\Feed\Service\Tracking\CompositeQuoteItemPriceResolver;
use AthosCommerce\Feed\Service\Tracking\CompositeSkuResolver;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item;

/**
 * Class CartViewModel
 *
 * This is view model for Cart Page
 *
 * @package AthosCommerce\Feed\ViewModel
 */
class CartViewModel implements ArgumentInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var CompositeQuoteItemPriceResolver
     */
    private $priceResolver;

    /**
     * @var CompositeSkuResolver
     */
    private $skuResolver;

    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @var array
     */
    private $productsSku = [];

    /**
     * @param Config $config
     * @param CompositeQuoteItemPriceResolver $priceResolver
     * @param CompositeSkuResolver $skuResolver
     * @param SerializerInterface $serializer
     * @param CheckoutSession $checkoutSession
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        Config                          $config,
        CompositeQuoteItemPriceResolver $priceResolver,
        CompositeSkuResolver            $skuResolver,
        SerializerInterface             $serializer,
        CheckoutSession                 $checkoutSession,
        AthosCommerceLogger             $logger
    )
    {
        $this->config = $config;
        $this->priceResolver = $priceResolver;
        $this->skuResolver = $skuResolver;
        $this->serializer = $serializer;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

    /**
     * @return array|array[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCartPageData(): array
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            if (!$quote || !$quote->getId()) {
                return [
                    'products' => []
                ];
            }

            $products = [];
            foreach ($quote->getAllVisibleItems() as $item) {
                if (!$item instanceof Item) {
                    continue;
                }
                $products[] = $this->formatQuoteItem($item);
            }
            return [
                'products' => $this->serializer->serialize($products)
            ];
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            return [
                'products' => []
            ];
        }
    }

    /**
     * @param Item $item
     * @return array
     */
    private function formatQuoteItem(Item $quoteItem): array
    {
        $product = $quoteItem->getProduct();

        return [
            'uid' => (string)$quoteItem->getItemId() ?? '',
            'name' => (string)$quoteItem->getName() ?? '',
            'sku' => $this->skuResolver->getProductSku($quoteItem),
            'qty' => $this->getProductQuantity($quoteItem),
            'price' => $this->priceResolver->getProductPrice($quoteItem),
        ];
    }

    /**
     * @param CartItemInterface $quoteItem
     * @return int|null
     */
    private function getProductQuantity(CartItemInterface $quoteItem): ?int
    {
        return (int)$quoteItem->getQty();
    }
}
