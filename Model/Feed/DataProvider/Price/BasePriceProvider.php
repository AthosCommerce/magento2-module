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
use AthosCommerce\Feed\Model\Feed\DataProvider\PricesProvider;

class BasePriceProvider implements PriceProviderInterface
{
    /**
     * @var Type
     */
    private $type;

    /**
     * BasePriceProvider constructor.
     * @param Type $type
     */
    public function __construct(
        Type $type
    ) {
        $this->type = $type;
    }

    /**
     * @param ProductInterface $product
     * @param array $ignoredFields
     * @return array
     */
    public function getPrices(ProductInterface $product, array $ignoredFields): array
    {
        $result = [];
        if (!in_array(PricesProvider::FINAL_PRICE_KEY, $ignoredFields)) {
            $result[PricesProvider::FINAL_PRICE_KEY] = $product
                ->getPriceInfo()
                ->getPrice(FinalPrice::PRICE_CODE)
                ->getMinimalPrice()
                ->getValue();
        }

        if (!in_array(PricesProvider::REGULAR_PRICE_KEY, $ignoredFields)) {
            $result[PricesProvider::REGULAR_PRICE_KEY] = $product
                ->getPriceInfo()
                ->getPrice(RegularPrice::PRICE_CODE)
                ->getValue();
        }

        if (!in_array(PricesProvider::MAX_PRICE_KEY, $ignoredFields)) {
            $result[PricesProvider::MAX_PRICE_KEY] = $product
                ->getPriceInfo()
                ->getPrice(FinalPrice::PRICE_CODE)
                ->getMaximalPrice()
                ->getValue();
        }

        return $result;
    }
}
