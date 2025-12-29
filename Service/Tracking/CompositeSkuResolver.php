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

use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use AthosCommerce\Feed\Api\LoggerInterface;

/**
 * Class SkuResolver
 *
 * In di.xml we can configure skuResolversPool.This class can resolve way by which we will get product SKU.
 *
 * @package AthosCommerce\Feed\Service
 */
class CompositeSkuResolver implements SkuResolverInterface
{
    /**
     * @var array
     */
    private $skuResolversPool;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SkuResolverInterface
     */
    private $defaultSkuResolver;

    /**
     * SkuResolver constructor.
     *
     * @param LoggerInterface $logger
     * @param SkuResolverInterface $defaultSkuResolver
     * @param array $skuResolversPool
     */
    public function __construct(
        LoggerInterface $logger,
        SkuResolverInterface $defaultSkuResolver,
        array $skuResolversPool = []
    ) {
        $this->logger             = $logger;
        $this->defaultSkuResolver = $defaultSkuResolver;
        $this->skuResolversPool   = $skuResolversPool;
    }

    /**
     * @param CartItemInterface|OrderItemInterface $product
     * @return string|null
     */
    public function getProductSku($product): ?string
    {
        if (isset($this->skuResolversPool[$product->getProductType()]) &&
            $this->skuResolversPool[$product->getProductType()] instanceof SkuResolverInterface) {
            return (string)$this->skuResolversPool[$product->getProductType()]->getProductSku($product);
        } elseif (!($this->skuResolversPool[$product->getProductType()] instanceof SkuResolverInterface)) {
            $this->logger->warning(get_class($this->skuResolversPool[$product->getProductType()]) . ' must implement ' . SkuResolverInterface::class);
        }

        return (string)$this->defaultSkuResolver->getProductSku($product);
    }
}
