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

namespace AthosCommerce\Feed\Service\Tracking;

use Magento\Quote\Api\Data\CartItemInterface;
use AthosCommerce\Feed\Api\LoggerInterface;

/**
 * Class QuoteItemPriceResolver
 *
 * In di.xml we can configure quoteItemPriceResolversPool.This class can resolve way by which we will get product price for quote item (cart page).
 *
 * @package AthosCommerce\Feed\Service
 */
class CompositeQuoteItemPriceResolver implements QuoteItemPriceResolverInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var QuoteItemPriceResolverInterface
     */
    private $defaultPriceResolver;

    /**
     * @var array
     */
    private $quoteItemPriceResolversPool;

    /**
     * QuoteItemPriceResolver constructor.
     *
     * @param LoggerInterface $logger
     * @param QuoteItemPriceResolverInterface $defaultPriceResolver
     * @param array $quoteItemPriceResolversPool
     */
    public function __construct(
        LoggerInterface                 $logger,
        QuoteItemPriceResolverInterface $defaultPriceResolver,
        array                           $quoteItemPriceResolversPool = []
    )
    {
        $this->logger = $logger;
        $this->defaultPriceResolver = $defaultPriceResolver;
        $this->quoteItemPriceResolversPool = $quoteItemPriceResolversPool;
    }

    /**
     * @param CartItemInterface $product
     * @return float|null
     */
    public function getProductPrice(CartItemInterface $product): ?float
    {
        if ((isset($this->quoteItemPriceResolversPool[$product->getProductType()]) &&
            $this->quoteItemPriceResolversPool[$product->getProductType()] instanceof QuoteItemPriceResolverInterface)
        ) {
            return (float)$this->quoteItemPriceResolversPool[$product->getProductType()]->getProductPrice($product);
        } elseif (isset($this->quoteItemPriceResolversPool[$product->getProductType()]) &&
            !($this->quoteItemPriceResolversPool[$product->getProductType()] instanceof QuoteItemPriceResolverInterface)
        ) {
            $this->logger->warning(
                get_class($this->quoteItemPriceResolversPool[$product->getProductType()]) . ' must implement ' . QuoteItemPriceResolverInterface::class
            );
        }

        return (float)$this->defaultPriceResolver->getProductPrice($product);
    }
}
