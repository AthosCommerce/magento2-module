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

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use AthosCommerce\Feed\Service\Config;
use AthosCommerce\Feed\Service\Tracking\CompositeQuoteItemPriceResolver;
use AthosCommerce\Feed\Service\Tracking\CompositeSkuResolver;
use Magento\Framework\Serialize\SerializerInterface;

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
     * @var array
     */
    private $productsSku = [];

    /**
     * CartViewModel constructor.
     *
     * @param Config $getAthoscommerceSiteId
     * @param CompositeQuoteItemPriceResolver $priceResolver
     * @param CompositeSkuResolver $skuResolver
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Config $config,
        CompositeQuoteItemPriceResolver $priceResolver,
        CompositeSkuResolver $skuResolver,
        SerializerInterface $serializer
    ) {
        $this->config = $config;
        $this->priceResolver         = $priceResolver;
        $this->skuResolver           = $skuResolver;
        $this->serializer            = $serializer;
    }

    /**
     * @return string|null
     */
    public function getAthoscommerceSiteId(): ?string
    {
        echo $this->config->getSiteId(); die;
        return 'test';
    }

    /**
     * @param array $quoteItems
     * @return string|null
     */
    public function getProducts(array $quoteItems): ?string
    {
        $this->productsSku = [];
        foreach ($quoteItems as $quoteItem) {
            $this->productsSku[] = $this->skuResolver->getProductSku($quoteItem);

            $products[] = [
                'price' => $this->priceResolver->getProductPrice($quoteItem),
                'sku' => $this->skuResolver->getProductSku($quoteItem),
                'qty' => $this->getProductQuantity($quoteItem)
            ];
        }
        return $this->serializer->serialize($products);
    }

    /**
     * @param CartItemInterface $quoteItem
     * @return int|null
     */
    private function getProductQuantity(CartItemInterface $quoteItem): ?int
    {
        return (int)$quoteItem->getQty();
    }

    /**
     * @return string|null
     */
    public function getProductsSku(): ?string
    {
        return $this->serializer->serialize($this->productsSku);
    }
}
