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

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Price;

use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\DataProvider\PricesProvider;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface;

class SimplePriceProvider implements PriceProviderInterface
{
    /**
     * @var ParentVariantResolver
     */
    private $parentVariantResolver;

    /**
     * @var ConfigurableOptionsProviderInterface
     */
    private $configurableOptionsProvider;

    /**
     * @param ParentVariantResolver $parentVariantResolver
     * @param ConfigurableOptionsProviderInterface $configurableOptionsProvider
     */
    public function __construct(
        ParentVariantResolver $parentVariantResolver,
        ConfigurableOptionsProviderInterface $configurableOptionsProvider
    ) {
        $this->parentVariantResolver = $parentVariantResolver;
        $this->configurableOptionsProvider = $configurableOptionsProvider;
    }

    /**
     * @param ProductInterface $product
     * @param array $ignoredFields
     * @param ProductInterface|null $resolvedParent
     * @return array
     */
    public function getPrices(
        ProductInterface $product,
        array $ignoredFields,
        ?ProductInterface $resolvedParent = null
    ): array {
        $result = [];
        $priceKeys = [
            PricesProvider::FINAL_PRICE_KEY,
            PricesProvider::REGULAR_PRICE_KEY,
            PricesProvider::MAX_PRICE_KEY,
        ];

        if (empty(array_diff($priceKeys, $ignoredFields))) {
            return $result;
        }

        $parent = $resolvedParent ?: $this->resolveFallbackParentProduct($product);

        if (!in_array(PricesProvider::FINAL_PRICE_KEY, $ignoredFields, true)) {
            $result[PricesProvider::FINAL_PRICE_KEY] = $this->getPriceValueByCode(
                $product,
                PricesProvider::FINAL_PRICE_KEY,
                $parent
            );
        }

        if (!in_array(PricesProvider::REGULAR_PRICE_KEY, $ignoredFields, true)) {
            $result[PricesProvider::REGULAR_PRICE_KEY] = $this->getPriceValueByCode(
                $product,
                PricesProvider::REGULAR_PRICE_KEY,
                $parent
            );
        }

        if (!in_array(PricesProvider::MAX_PRICE_KEY, $ignoredFields, true)) {
            $result[PricesProvider::MAX_PRICE_KEY] = $this->getPriceValueByCode(
                $product,
                PricesProvider::MAX_PRICE_KEY,
                $parent
            );
        }

        return $result;
    }

    /**
     * Backward-compatible fallback when row context is unavailable.
     *
     * Preference:
     * 1. configurable parent
     * 2. grouped parent
     * 3. first available parent
     *
     * @param ProductInterface $product
     * @return ProductInterface|null
     */
    private function resolveFallbackParentProduct(ProductInterface $product): ?ProductInterface
    {
        if (!$product instanceof Product) {
            return null;
        }

        $parents = $this->parentVariantResolver->getParentProducts($product);

        if (empty($parents)) {
            return null;
        }

        foreach ($parents as $parent) {
            if ($parent->getTypeId() === Constant::CONFIGURABLE_TYPE) {
                return $parent;
            }
        }

        foreach ($parents as $parent) {
            if ($parent->getTypeId() === Constant::GROUPED_TYPE) {
                return $parent;
            }
        }

        return $parents[0];
    }

    /**
     * Returns price value for a given price key.
     * If resolved parent exists and has a valid price, it takes precedence.
     * Falls back to the product’s own price.
     *
     * @param ProductInterface $product
     * @param string $priceKey
     * @param ProductInterface|null $parent
     * @return float
     */
    private function getPriceValueByCode(
        ProductInterface $product,
        string $priceKey,
        ?ProductInterface $parent = null
    ): float {
        switch ($priceKey) {
            case PricesProvider::FINAL_PRICE_KEY:
                if ($parent && method_exists($parent, 'getPriceInfo')) {
                    $value = $parent->getPriceInfo()
                        ->getPrice(FinalPrice::PRICE_CODE)
                        ->getMinimalPrice()
                        ->getValue();
                } else {
                    $value = $product->getPriceInfo()
                        ->getPrice(FinalPrice::PRICE_CODE)
                        ->getValue();
                }
                break;

            case PricesProvider::REGULAR_PRICE_KEY:
                if ($parent && method_exists($parent, 'getPriceInfo')) {
                    $value = $parent->getPriceInfo()
                        ->getPrice(RegularPrice::PRICE_CODE)
                        ->getValue();
                } else {
                    $value = $product->getPriceInfo()
                        ->getPrice(RegularPrice::PRICE_CODE)
                        ->getValue();
                }
                break;

            case PricesProvider::MAX_PRICE_KEY:
                $value = $this->resolveMaxPrice($product, $parent);
                break;

            default:
                $value = $product->getPriceInfo()
                    ->getPrice(FinalPrice::PRICE_CODE)
                    ->getValue();
        }

        return (float)$value;
    }

    /**
     * @param ProductInterface $product
     * @param ProductInterface|null $parent
     * @return float
     */
    private function resolveMaxPrice(
        ProductInterface $product,
        ?ProductInterface $parent = null
    ): float {
        if ($parent && method_exists($parent, 'getTypeId')) {
            if ($parent->getTypeId() === Constant::CONFIGURABLE_TYPE) {
                $maximumAmount = method_exists($parent, 'hasMaxPrice') && $parent->hasMaxPrice()
                    ? (float)$parent->getMaxPrice()
                    : null;

                if ($maximumAmount === null) {
                    $childProducts = $this->configurableOptionsProvider->getProducts($parent);

                    foreach ($childProducts as $variant) {
                        $variantAmount = $variant->getPriceInfo()
                            ->getPrice(FinalPrice::PRICE_CODE)
                            ->getAmount()
                            ->getValue();

                        if ($maximumAmount === null || $variantAmount > $maximumAmount) {
                            $maximumAmount = $variantAmount;
                        }
                    }
                }

                return (float)$maximumAmount;
            }

            if ($parent->getTypeId() === Constant::GROUPED_TYPE) {
                $maximumAmount = null;
                $childProducts = $parent->getTypeInstance()->getAssociatedProducts($parent);

                foreach ($childProducts as $variant) {
                    $variantAmount = $variant->getPriceInfo()
                        ->getPrice(FinalPrice::PRICE_CODE)
                        ->getAmount()
                        ->getValue();

                    if ($maximumAmount === null || $variantAmount > $maximumAmount) {
                        $maximumAmount = $variantAmount;
                    }
                }

                if ($maximumAmount !== null) {
                    return (float)$maximumAmount;
                }
            }
        }

        return (float)$product->getPriceInfo()
            ->getPrice(FinalPrice::PRICE_CODE)
            ->getMaximalPrice()
            ->getValue();
    }
}
