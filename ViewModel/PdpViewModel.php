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
use AthosCommerce\Feed\Service\Tracking\IdProviderInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
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
     * @var IdProviderInterface
     */
    private $idProvider;


    /**
     * @param Config $config
     * @param Registry $registry
     * @param IdProviderInterface $idProvider
     * @param StoreManagerInterface $storeManager
     * @param SerializerInterface $serializer
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        Config                $config,
        Registry              $registry,
        IdProviderInterface   $idProvider,
        StoreManagerInterface $storeManager,
        SerializerInterface   $serializer,
        AthosCommerceLogger   $logger
    )
    {
        $this->config = $config;
        $this->registry = $registry;
        $this->idProvider = $idProvider;
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
        $uid = $this->idProvider->getItemId($product);
        $parentId = $this->idProvider->getItemParentId($product);

        if ($parentId !== null && $parentId !== '' && $parentId !== $uid) {
            $uid = $parentId . '_' . $uid;
            $parentId = (string)$parentId;
        }

        $data = $this->serializer->serialize([
            'uid' => (string)$uid,
            'sku' => $this->idProvider->getItemSku($product),
            'parentId' => $parentId,
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
