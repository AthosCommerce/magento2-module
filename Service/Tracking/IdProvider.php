<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Service\Tracking;

use AthosCommerce\Feed\Service\Tracking\IdProviderInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Api\LinkManagementInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;

class IdProvider implements IdProviderInterface
{
    /**
     * @var LinkManagementInterface
     */
    private $linkManagement;

    /**
     * @var Configurable
     */
    private $configurableType;

    /**
     * @var Grouped
     */
    private $groupedType;

    /**
     * @param LinkManagementInterface $linkManagement
     * @param Configurable $configurableType
     * @param Grouped $groupedType
     */
    public function __construct(
        LinkManagementInterface $linkManagement,
        Configurable            $configurableType,
        Grouped                 $groupedType
    )
    {
        $this->linkManagement = $linkManagement;
        $this->configurableType = $configurableType;
        $this->groupedType = $groupedType;
    }

    /**
     * @param ProductInterface $product
     * @return string
     */
    public function getItemId(ProductInterface $product): string
    {
        if ($product->getTypeId() === Configurable::TYPE_CODE || $product->getTypeId() === Grouped::TYPE_CODE) {
            return $this->getChildProductId($product);
        }

        return (string)$product->getId();
    }

    /**
     * @param ProductInterface $product
     * @return string
     */
    public function getItemSku(ProductInterface $product): string
    {
        if ($product->getTypeId() === Configurable::TYPE_CODE || $product->getTypeId() === Grouped::TYPE_CODE) {
            return $this->getChildProductSku($product);
        }

        return (string)$product->getSku();
    }

    /**
     * @param ProductInterface $product
     * @return string
     */
    public function getItemParentId(ProductInterface $product): string
    {
        $productId = (string)$product->getId();

        if ($product->getTypeId() === Configurable::TYPE_CODE || $product->getTypeId() === Grouped::TYPE_CODE) {
            return $productId;
        }

        return $this->getParentId($product) ?? $productId;
    }

    /**
     * @param ProductInterface $product
     * @return string
     */
    private function getChildProductId(ProductInterface $product): string
    {
        $childProduct = $this->getChildProduct($product);
        $childProductId = $childProduct ? $childProduct->getId() : null;

        return $childProductId
            ? (string)$childProductId
            : (string)$product->getId();
    }

    /**
     * @param ProductInterface $product
     * @return string
     */
    private function getChildProductSku(ProductInterface $product): string
    {
        $childProduct = $this->getChildProduct($product);
        $childProductSku = $childProduct ? $childProduct->getSku() : null;

        return $childProductSku
            ? (string)$childProductSku
            : (string)$product->getSku();
    }

    /**
     * @param ProductInterface $product
     * @return ProductInterface|null
     */
    private function getChildProduct(ProductInterface $product): ?ProductInterface
    {
        if ($product->getTypeId() === Configurable::TYPE_CODE) {
            return $this->getConfigurableChildProduct($product);
        }

        if ($product->getTypeId() === Grouped::TYPE_CODE) {
            return $this->getGroupedChildProduct($product);
        }

        return null;
    }

    /**
     * @param ProductInterface $product
     * @return ProductInterface|null
     */
    private function getConfigurableChildProduct(ProductInterface $product): ?ProductInterface
    {
        $childProducts = $this->linkManagement->getChildren($product->getSku());

        foreach ($childProducts as $childProduct) {
            if (method_exists($childProduct, 'isAvailable') && $childProduct->isAvailable()) {
                return $childProduct;
            }
        }

        return null;
    }

    /**
     * @param ProductInterface $product
     * @return ProductInterface|null
     */
    private function getGroupedChildProduct(ProductInterface $product): ?ProductInterface
    {
        $groupedChildProducts = $product->getTypeInstance()->getAssociatedProducts($product);

        foreach ($groupedChildProducts as $childProduct) {
            if (method_exists($childProduct, 'isAvailable') && $childProduct->isAvailable()) {
                return $childProduct;
            }
        }

        return null;
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
}
