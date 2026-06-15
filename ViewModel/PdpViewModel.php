<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\ViewModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

class PdpViewModel implements ArgumentInterface
{
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Registry              $registry,
        StoreManagerInterface $storeManager
    )
    {
        $this->registry = $registry;
        $this->storeManager = $storeManager;

    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTrackingData(): array
    {
        $product = $this->getCurrentProduct();
        if (!$product || !(int)$product->getId()) {
            return [];
        }

        return [
            'uid' => (string)$product->getDataUsingMethod('entity_id'),
            'sku' => (string)$product->getSku(),
            'price' => $this->getProductPrice($product),
            'currency' => $this->getCurrency(),
        ];
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
