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

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentDataContextManager;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentRelationsContext;
use AthosCommerce\Feed\Model\Feed\DataProvider\PricesProvider;
use Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface;

class SimplePriceProvider implements PriceProviderInterface
{
    /**
     * @var ParentDataContextManager
     */
    private $parentDataContextManager;
    /**
     * @var ParentRelationsContext
     */
    private $parentRelationsContext;
    /**
     * @var ConfigurableOptionsProviderInterface
     */
    private $configurableOptionsProvider;

    /**
     * @param ParentDataContextManager $parentDataContextManager
     */
    public function __construct(
        ParentDataContextManager $parentDataContextManager,
        ParentRelationsContext $parentRelationsContext,
        ConfigurableOptionsProviderInterface $configurableOptionsProvider
    ) {
        $this->parentDataContextManager = $parentDataContextManager;
        $this->parentRelationsContext = $parentRelationsContext;
        $this->configurableOptionsProvider = $configurableOptionsProvider;
    }

    /**
     * @param ProductInterface $product
     * @param array $ignoredFields
     *
     * @return array
     */
    public function getPrices(ProductInterface $product, array $ignoredFields): array
    {
        $result = [];
        $priceKeys = [
            PricesProvider::FINAL_PRICE_KEY,
            PricesProvider::REGULAR_PRICE_KEY,
            PricesProvider::MAX_PRICE_KEY,
        ];

        //Return blank array if all keys are part of ignored fields
        if (empty(array_diff($priceKeys, $ignoredFields))) {
            return $result;
        }
        //$parent = $this->parentDataContextManager->getParentsDataByProductId((int)$product->getId());
        $parent = $this->parentRelationsContext->getParentsByChildId((int)$product->getId());

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
     * Returns price value for a given price code.
     * If parent exists and has a valid price, it takes precedence.
     * Falls back to the product’s own price.
     *
     * @param ProductInterface $product
     * @param string $priceCode
     * @param ProductInterface|null $parent
     *
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
                    $value = $product
                        ->getPriceInfo()
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
                    $value = $product
                        ->getPriceInfo()
                        ->getPrice(RegularPrice::PRICE_CODE)
                        ->getValue();
                }
                break;
            case PricesProvider::MAX_PRICE_KEY:
                if ($parent) {
                    $maximumAmount = $parent->hasMaxPrice()
                        ? (float)$parent->getMaxPrice()
                        : null;

                    if (is_null($maximumAmount)) {
                        $childProducts = $this->configurableOptionsProvider->getProducts($product);
                        foreach ($childProducts as $variant) {
                            $variantAmount = $variant->getPriceInfo()->getPrice(FinalPrice::PRICE_CODE)->getAmount();
                            if (!$maximumAmount || ($variantAmount->getValue() > $maximumAmount)) {
                                $maximumAmount = $variantAmount->getValue();
                            }
                        }
                    }

                    $value = $maximumAmount;
                } else {
                    $value = $product
                        ->getPriceInfo()
                        ->getPrice(RegularPrice::PRICE_CODE)
                        ->getValue();
                }

                break;
            default:
                $value = $product->getPriceInfo()
                    ->getPrice(FinalPrice::PRICE_CODE)
                    ->getValue();
        }

        return (float)$value;
    }
}
