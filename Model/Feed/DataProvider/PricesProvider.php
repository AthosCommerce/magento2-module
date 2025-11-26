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

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\Framework\Serialize\Serializer\Json;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Price\ProviderResolverInterface;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;

class PricesProvider implements DataProviderInterface
{
    const REGULAR_PRICE_KEY = 'regular_price';
    const FINAL_PRICE_KEY = 'final_price';
    const MAX_PRICE_KEY = 'max_price';

    /**
     * @var Json
     */
    private $json;
    /**
     * @var ProviderResolverInterface
     */
    private $priceProviderResolver;

    /**
     * PricesProvider constructor.
     *
     * @param Json $json
     * @param ProviderResolverInterface $priceProviderResolver
     */
    public function __construct(
        Json $json,
        ProviderResolverInterface $priceProviderResolver
    ) {
        $this->json = $json;
        $this->priceProviderResolver = $priceProviderResolver;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $ignoredFields = $feedSpecification->getIgnoreFields();
        foreach ($products as &$product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            $priceProvider = $this->priceProviderResolver->resolve($productModel);
            $product = array_merge($product, $priceProvider->getPrices($productModel, $ignoredFields));

            if ($feedSpecification->getIncludeTierPricing() && !in_array('tier_pricing', $ignoredFields)) {
                $product['tier_pricing'] = $this->json->serialize($productModel->getTierPrice());
            }
        }

        return $products;
    }

    /**
     *
     */
    public function reset(): void
    {
        // do nothing
    }

    /**
     *
     */
    public function resetAfterFetchItems(): void
    {
        // do nothing
    }
}
