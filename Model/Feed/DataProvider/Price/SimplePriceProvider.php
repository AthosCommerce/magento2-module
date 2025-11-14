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
use AthosCommerce\Feed\Model\Feed\DataProvider\PricesProvider;

class SimplePriceProvider implements PriceProviderInterface
{
    /**
     * @var ParentDataContextManager
     */
    private $parentDataContextManager;

    /**
     * @param ParentDataContextManager $parentDataContextManager
     */
    public function __construct(
        ParentDataContextManager $parentDataContextManager,
    ) {
        $this->parentDataContextManager = $parentDataContextManager;
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

        if (empty(array_diff($priceKeys, $ignoredFields))) {
            return $result;
        }
        if (!$product instanceof ProductInterface) {
            return $result;
        }

        $parent = $this->parentDataContextManager->getParentsDataByProductId((int)$product->getId());

        if (!in_array(PricesProvider::FINAL_PRICE_KEY, $ignoredFields, true)) {
            $result[PricesProvider::FINAL_PRICE_KEY] = $this->getPriceValueByCode(
                $product,
                FinalPrice::PRICE_CODE,
                $parent
            );
        }

        if (!in_array(PricesProvider::REGULAR_PRICE_KEY, $ignoredFields, true)) {
            $result[PricesProvider::REGULAR_PRICE_KEY] = $this->getPriceValueByCode(
                $product,
                RegularPrice::PRICE_CODE,
                $parent
            );
        }

        //TODO:: Check against the existing feed
        if (!in_array(PricesProvider::MAX_PRICE_KEY, $ignoredFields, true)) {
            $result[PricesProvider::MAX_PRICE_KEY] = $this->getPriceValueByCode(
                $product,
                FinalPrice::PRICE_CODE,
                $parent,
                'max'
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
     * @param string $mode ('min'|'max')
     *
     * @return float
     */
    private function getPriceValueByCode(
        ProductInterface $product,
        string $priceCode,
        ?ProductInterface $parent = null,
        string $mode = 'min'
    ): float {
        $target = $parent
            ?: $product;
        $priceModel = $target->getPriceInfo()->getPrice($priceCode);
        $value = 0;
        if ($mode === 'max') {
            if (method_exists($priceModel, 'getMaximalPrice')) {
                $value = $priceModel->getMaximalPrice()->getValue();
            }
        } else {
            if (method_exists($priceModel, 'getMinimalPrice')) {
                $value = $priceModel->getMaximalPrice()->getValue();
            }
        }

        if ($parent && (!$value || $value <= 0.0)) {
            $fallbackModel = $product->getPriceInfo()->getPrice($priceCode);
            if ($mode === 'max') {
                if (method_exists($fallbackModel, 'getMaximalPrice')) {
                    $value = $fallbackModel->getMaximalPrice()->getValue();
                }
            } else {
                if (method_exists($fallbackModel, 'getMinimalPrice')) {
                    $value = $fallbackModel->getMinimalPrice()->getValue();
                }
            }
        }

        return (float)$value;
    }
}
