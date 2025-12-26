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

use Magento\Sales\Api\Data\OrderItemInterface;
use AthosCommerce\Feed\Api\LoggerInterface;

/**
 * Class OrderItemPriceResolver
 *
 * In di.xml we can configure orderItemPriceResolversPool.This class can resolve way by which we will get product price for order item (checkout success page).
 *
 * @package AthosCommerce\Feed\Service
 */
class CompositeOrderItemPriceResolver implements OrderItemPriceResolverInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrderItemPriceResolverInterface
     */
    private $defaultPriceResolver;

    /**
     * @var array
     */
    private $orderItemPriceResolversPool;

    /**
     * OrderItemPriceResolver constructor.
     *
     * @param LoggerInterface $logger
     * @param OrderItemPriceResolverInterface $defaultPriceResolver
     * @param array $orderItemPriceResolversPool
     */
    public function __construct(
        LoggerInterface                 $logger,
        OrderItemPriceResolverInterface $defaultPriceResolver,
        array                           $orderItemPriceResolversPool = []
    )
    {
        $this->logger = $logger;
        $this->defaultPriceResolver = $defaultPriceResolver;
        $this->orderItemPriceResolversPool = $orderItemPriceResolversPool;
    }

    /**
     * @param OrderItemInterface $product
     * @return float|null
     */
    public function getProductPrice(OrderItemInterface $product): ?float
    {
        if ((isset($this->orderItemPriceResolversPool[$product->getProductType()]) &&
            $this->orderItemPriceResolversPool[$product->getProductType()] instanceof OrderItemPriceResolverInterface
        )) {
            return (float)$this->orderItemPriceResolversPool[$product->getProductType()]->getProductPrice($product);

        } elseif (isset($this->orderItemPriceResolversPool[$product->getProductType()]) &&
            !($this->orderItemPriceResolversPool[$product->getProductType()] instanceof OrderItemPriceResolverInterface)
        ) {
            $this->logger->warning(
                get_class($this->orderItemPriceResolversPool[$product->getProductType()]) . ' must implement ' . OrderItemPriceResolverInterface::class
            );
        }

        return (float)$this->defaultPriceResolver->getProductPrice($product);
    }
}
