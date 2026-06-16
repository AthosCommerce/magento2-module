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
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Model\StoreManagerInterface;

class PdpViewModel implements ArgumentInterface
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var Configurable
     */
    private $configurableType;
    /**
     * @var Grouped
     */
    private $groupedType;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param Config $config
     * @param Registry $registry
     * @param Configurable $configurableType
     * @param Grouped $groupedType
     * @param StoreManagerInterface $storeManager
     * @param SerializerInterface $serializer
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        Config                $config,
        Registry              $registry,
        Configurable          $configurableType,
        Grouped               $groupedType,
        StoreManagerInterface $storeManager,
        SerializerInterface   $serializer,
        AthosCommerceLogger   $logger
    )
    {
        $this->config = $config;
        $this->registry = $registry;
        $this->configurableType = $configurableType;
        $this->groupedType = $groupedType;
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @return string
     */
    public function getProductPageData(): string
    {
        if (true !== $this->config->shouldRender()) {
            return '';
        }

        $product = $this->getCurrentProduct();
        if (!$product || !(int)$product->getId()) {
            return '';
        }

        $data = $this->serializer->serialize([
            'uid' => (string)$product->getDataUsingMethod('entity_id'),
            'sku' => (string)$product->getSku(),
            'parentId' => $this->getParentId($product),
            'price' => $this->getProductPrice($product),
            'currency' => $this->getCurrency(),
        ]);
        if (!is_string($data)) {
            $data = '';
        }
        return $data;
    }

    /**
     * @return ProductInterface|null
     */
    private function getCurrentProduct(): ?ProductInterface
    {
        $product = $this->registry->registry('current_product');
        return $product instanceof ProductInterface ? $product : null;
    }

    /**
     * @return null|string
     */
    private function getCurrency(): ?string
    {
        try {
            return (string)$this->storeManager->getStore()->getCurrentCurrencyCode();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    /**
     * @param ProductInterface $product
     * @return string|null
     */
    private function getParentId(ProductInterface $product): ?string
    {
        $parentIds = $this->configurableType->getParentIdsByChild((int)$product->getId());
        if (!empty($parentIds)) {
            return (string)reset($parentIds);
        }

        $groupedParentIds = $this->groupedType->getParentIdsByChild((int)$product->getId());
        if (!empty($groupedParentIds)) {
            return (string)reset($groupedParentIds);
        }

        return null;
    }

    /**
     * @param ProductInterface $product
     * @return float
     */
    private function getProductPrice(ProductInterface $product): float
    {
        $finalPrice = (float)$product->getFinalPrice();
        if ($finalPrice > 0) {
            return $finalPrice;
        }

        $price = (float)$product->getPrice();
        return $price;
    }
}
