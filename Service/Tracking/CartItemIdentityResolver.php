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

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item;

class CartItemIdentityResolver
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        SerializerInterface        $serializer
    )
    {
        $this->productRepository = $productRepository;
        $this->serializer = $serializer;
    }

    /**
     * Returns the tracked uid.
     *
     * Rules:
     * - Standalone simple/virtual/downloadable/bundle => own product id
     * - Configurable added from parent PDP => selected child simple id
     * - Grouped added from parent PDP => associated child simple id
     *
     * @param CartItemInterface $cartItem
     * @return string|null
     */
    public function getUid(CartItemInterface $cartItem): ?string
    {
        $product = $this->getResolvedProduct($cartItem);

        if ($product && (int)$product->getId()) {
            return (string)$product->getId();
        }

        $productId = $cartItem->getProductId();
        return $productId ? (string)$productId : null;
    }

    /**
     * Returns the tracked parentId.
     *
     * Rules:
     * - Configurable parent add => configurable parent product id
     * - Grouped parent add => grouped parent product id
     * - Standalone add => uid
     *
     * @param CartItemInterface $cartItem
     * @return string|null
     */
    public function getParentId(CartItemInterface $cartItem): ?string
    {
        $uid = $this->getUid($cartItem);
        if ($uid === null || $uid === '') {
            return null;
        }

        $configurableParentId = $this->getConfigurableParentIdFromQuoteItem($cartItem);
        if ($configurableParentId !== null && $configurableParentId !== '') {
            return $configurableParentId;
        }

        $groupedParentId = $this->getGroupedParentIdFromQuoteItem($cartItem);
        if ($groupedParentId !== null && $groupedParentId !== '') {
            return $groupedParentId;
        }

        return $uid;
    }

    /**
     * Returns the tracked sku.
     *
     * Rules:
     * - Use child sku for configurable/grouped child items
     * - Use own sku for standalone products
     *
     * @param CartItemInterface $cartItem
     * @return string
     */
    public function getSku(CartItemInterface $cartItem): string
    {
        if ($cartItem instanceof Item) {
            $product = $cartItem->getProduct();

            if ($product && $product->getTypeId() === \Magento\Bundle\Model\Product\Type::TYPE_CODE) {

                return (string)$product->getSku();
            }
        }

        $product = $this->getResolvedProduct($cartItem);

        if ($product && $product->getSku()) {
            return (string)$product->getSku();
        }

        if (method_exists($cartItem, 'getSku') && $cartItem->getSku()) {
            return (string)$cartItem->getSku();
        }

        return '';
    }

    /**
     * Resolves the actual tracked product.
     *
     * For configurable parent quote items, this attempts to resolve the selected simple child.
     * For grouped associated items and standalone products, the cart item product is already correct.
     *
     * @param CartItemInterface $cartItem
     * @return Product|null
     */
    private function getResolvedProduct(CartItemInterface $cartItem): ?Product
    {
        $product = null;

        if (!$cartItem instanceof Item) {
            return null;
        }

        $product = $cartItem->getProduct();
        if (!$product) {
            return null;
        }

        $simpleProductOption = $cartItem->getOptionByCode('simple_product');
        if ($simpleProductOption) {
            $optionProduct = $simpleProductOption->getProduct();
            if ($optionProduct && (int)$optionProduct->getId()) {
                return $optionProduct;
            }
        }

        $buyRequest = $cartItem->getOptionByCode('info_buyRequest');
        if ($buyRequest && $buyRequest->getValue()) {
            try {
                $buyRequestData = $this->serializer->unserialize($buyRequest->getValue());
            } catch (\InvalidArgumentException $exception) {
                return $product;
            }

            if (is_array($buyRequestData) && !empty($buyRequestData['selected_configurable_option'])) {
                $selectedId = (int)$buyRequestData['selected_configurable_option'];
                if ($selectedId) {
                    try {
                        $resolved = $this->productRepository->getById($selectedId);
                        if ($resolved && (int)$resolved->getId()) {
                            return $resolved;
                        }
                    } catch (\Exception $exception) {
                        return $product;
                    }
                }
            }
        }

        return $product;
    }

    /**
     * Returns configurable parent only if quote item actually looks like configurable-parent add.
     *
     * This prevents visible child simples from being treated as configurable parents
     * merely because they have a configurable parent in catalog.
     *
     * @param CartItemInterface $cartItem
     * @return string|null
     */
    private function getConfigurableParentIdFromQuoteItem(CartItemInterface $cartItem): ?string
    {
        $product = null;
        $hasConfigurableContext = false;

        if (!$cartItem instanceof Item) {
            return null;
        }

        $hasConfigurableContext =
            $cartItem->getOptionByCode('simple_product') ||
            $cartItem->getOptionByCode('attributes') ||
            $cartItem->getOptionByCode('super_attribute');

        if (!$hasConfigurableContext) {
            return null;
        }

        $product = $cartItem->getProduct();
        if (!$product || !(int)$product->getId()) {
            return null;
        }

        return (string)$product->getId();
    }

    /**
     * Returns grouped parent only if quote item was actually added through grouped parent flow.
     *
     * Expected grouped buy request payload:
     * {
     *   "super_product_config": {
     *     "product_type": "grouped",
     *     "product_id": "19"
     *   }
     * }
     *
     * This prevents visible associated simples from being treated as grouped-parent adds
     * merely because they belong to a grouped product in catalog.
     *
     * @param CartItemInterface $cartItem
     * @return string|null
     */
    private function getGroupedParentIdFromQuoteItem(CartItemInterface $cartItem): ?string
    {
        $buyRequest = null;
        $buyRequestArray = array();
        $productConfig = null;

        if (!method_exists($cartItem, 'getOptionByCode')) {
            return null;
        }

        $buyRequest = $cartItem->getOptionByCode('info_buyRequest');
        if (!$buyRequest || !$buyRequest->getValue()) {
            return null;
        }

        try {
            $buyRequestArray = $this->serializer->unserialize($buyRequest->getValue());
        } catch (\InvalidArgumentException $exception) {
            return null;
        }

        if (!is_array($buyRequestArray)) {
            return null;
        }

        $productConfig = $buyRequestArray['super_product_config'] ?? null;
        if (!is_array($productConfig)) {
            return null;
        }

        if (
            !empty($productConfig['product_type']) &&
            $productConfig['product_type'] === Grouped::TYPE_CODE &&
            !empty($productConfig['product_id'])
        ) {
            return (string)$productConfig['product_id'];
        }

        return null;
    }
}
