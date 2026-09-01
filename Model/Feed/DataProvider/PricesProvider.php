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

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\Framework\Serialize\Serializer\Json;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Price\ProviderResolverInterface;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;

class PricesProvider implements DataProviderInterface
{
    public const REGULAR_PRICE_KEY = 'regular_price';
    public const FINAL_PRICE_KEY = 'final_price';
    public const MAX_PRICE_KEY = 'max_price';

    /**
     * @var Json
     */
    private $json;
    /**
     * @var ProviderResolverInterface
     */
    private $priceProviderResolver;
    /**
     * @var ParentVariantResolver
     */
    private $parentVariantResolver;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @var array<int, Product|null>
     */
    private $resolvedParentCache = [];

    /**
     * @param Json $json
     * @param ProviderResolverInterface $priceProviderResolver
     * @param ParentVariantResolver $parentVariantResolver
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        Json                      $json,
        ProviderResolverInterface $priceProviderResolver,
        ParentVariantResolver     $parentVariantResolver,
        AthosCommerceLogger       $logger,
    )
    {
        $this->json = $json;
        $this->priceProviderResolver = $priceProviderResolver;
        $this->parentVariantResolver = $parentVariantResolver;
        $this->logger = $logger;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $this->logger->info("[PricesProvider] Started");

        $ignoredFields = $feedSpecification->getIgnoreFields();
        foreach ($products as &$product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }
            $resolvedParent = null;

            $priceProvider = $this->priceProviderResolver->resolve($productModel);
            $isStandaloneProduct = (bool)($product[Constant::IS_STANDALONE_PRODUCT_KEY] ?? false);
            if (false === $isStandaloneProduct) {
                $resolvedParent = $this->resolveParentProductForRow($product, $productModel);
            }
            $product = array_merge(
                $product,
                $priceProvider->getPrices($productModel, $ignoredFields, $resolvedParent)
            );

            if ($feedSpecification->getIncludeTierPricing() && !in_array('tier_pricing', $ignoredFields)) {
                $product['tier_pricing'] = $this->json->serialize($productModel->getTierPrice());
            }
        }
        $this->logger->info("[PricesProvider] Completed");
        return $products;
    }

    /**
     *
     */
    public function reset(): void
    {
        $this->resolvedParentCache = [];
    }

    /**
     *
     */
    public function resetAfterFetchItems(): void
    {
        $this->reset();
    }

    /**
     * @param array $row
     * @param Product $productModel
     * @return Product|null
     */
    private function resolveParentProductForRow(array $row, Product $productModel): ?Product
    {
        $productId = (int)$productModel->getId();
        if (!array_key_exists($productId, $this->resolvedParentCache)) {
            $this->resolvedParentCache[$productId] = $this->parentVariantResolver->resolveParentProductForRow($row, $productModel);
        }

        return $this->resolvedParentCache[$productId];
    }
}
