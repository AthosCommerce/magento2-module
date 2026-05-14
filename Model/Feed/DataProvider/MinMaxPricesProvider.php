<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentRelationsContext;
use AthosCommerce\Feed\Model\Feed\DataProvider\Price\BasePriceProvider;
use Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;

class MinMaxPricesProvider implements DataProviderInterface
{
    /**
     * @var ParentRelationsContext
     */
    private ParentRelationsContext $parentRelationsContext;

    /**
     * @var ConfigurableOptionsProviderInterface
     */
    private ConfigurableOptionsProviderInterface $configurableOptionsProvider;

    /**
     * @var BasePriceProvider
     */
    private BasePriceProvider $basePriceProvider;

    /**
     * Runtime cache
     *
     * @var array<string, array>
     */
    private array $cache = [];

    /**
     * @param ParentRelationsContext $parentRelationsContext
     * @param ConfigurableOptionsProviderInterface $configurableOptionsProvider
     * @param BasePriceProvider $basePriceProvider
     */
    public function __construct(
        ParentRelationsContext $parentRelationsContext,
        ConfigurableOptionsProviderInterface $configurableOptionsProvider,
        BasePriceProvider $basePriceProvider
    ) {
        $this->parentRelationsContext = $parentRelationsContext;
        $this->configurableOptionsProvider = $configurableOptionsProvider;
        $this->basePriceProvider = $basePriceProvider;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     */
    public function getData(
        array $products,
        FeedSpecificationInterface $feedSpecification
    ): array {
        $ignoredFields = $feedSpecification->getIgnoreFields();

        foreach ($products as &$product) {
            /** @var Product|null $productModel */
            $productModel = $product['product_model'] ?? null;

            if (!$productModel instanceof ProductInterface) {
                continue;
            }

            $product = array_merge(
                $product,
                $this->getMinMaxPrices(
                    $productModel,
                    $ignoredFields
                )
            );
        }

        return $products;
    }

    /**
     * @param ProductInterface $product
     * @param array $ignoredFields
     *
     * @return array
     */
    private function getMinMaxPrices(
        ProductInterface $product,
        array $ignoredFields
    ): array {
        $parent = $this->parentRelationsContext
            ->getParentsByChildId((int)$product->getId());

        if ($parent instanceof ProductInterface) {
            $cacheKey = 'parent_' . (int)$parent->getId();

            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }

            $variants = $this->configurableOptionsProvider
                ->getProducts($parent);

            if (empty($variants)) {
                $variants = [$product];
            }

            return $this->cache[$cacheKey] = $this->aggregatePrices(
                $variants,
                $ignoredFields
            );
        }

        $cacheKey = 'self_' . (int)$product->getId();

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        return $this->cache[$cacheKey] = $this->aggregatePrices(
            [$product],
            $ignoredFields
        );
    }

    /**
     * @param ProductInterface[] $products
     * @param array $ignoredFields
     *
     * @return array
     */
    private function aggregatePrices(
        array $products,
        array $ignoredFields
    ): array {
        $regularPrices = [];
        $finalPrices = [];
        $maxPrices = [];

        foreach ($products as $product) {
            $prices = $this->basePriceProvider
                ->getPrices($product, $ignoredFields);

            if (isset($prices[PricesProvider::REGULAR_PRICE_KEY])) {
                $regularPrices[] =
                    (float)$prices[PricesProvider::REGULAR_PRICE_KEY];
            }

            if (isset($prices[PricesProvider::FINAL_PRICE_KEY])) {
                $finalPrices[] =
                    (float)$prices[PricesProvider::FINAL_PRICE_KEY];
            }

            if (isset($prices[PricesProvider::MAX_PRICE_KEY])) {
                $maxPrices[] =
                    (float)$prices[PricesProvider::MAX_PRICE_KEY];
            }
        }

        return [
            'ss_minimums' => [
                PricesProvider::REGULAR_PRICE_KEY =>
                    !empty($regularPrices)
                        ? min($regularPrices)
                        : 0.0,

                PricesProvider::FINAL_PRICE_KEY =>
                    !empty($finalPrices)
                        ? min($finalPrices)
                        : 0.0,

                PricesProvider::MAX_PRICE_KEY =>
                    !empty($maxPrices)
                        ? min($maxPrices)
                        : 0.0,
            ],

            'ss_maximums' => [
                PricesProvider::REGULAR_PRICE_KEY =>
                    !empty($regularPrices)
                        ? max($regularPrices)
                        : 0.0,

                PricesProvider::FINAL_PRICE_KEY =>
                    !empty($finalPrices)
                        ? max($finalPrices)
                        : 0.0,

                PricesProvider::MAX_PRICE_KEY =>
                    !empty($maxPrices)
                        ? max($maxPrices)
                        : 0.0,
            ],
        ];
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->cache = [];
    }

    /**
     * @return void
     */
    public function resetAfterFetchItems(): void
    {
        // do nothing
    }
}
