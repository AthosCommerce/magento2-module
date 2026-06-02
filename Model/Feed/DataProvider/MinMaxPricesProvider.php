<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
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
     * @var AthosCommerceLogger
     */
    private AthosCommerceLogger $logger;

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
        ParentRelationsContext               $parentRelationsContext,
        ConfigurableOptionsProviderInterface $configurableOptionsProvider,
        BasePriceProvider                    $basePriceProvider,
        AthosCommerceLogger                  $logger,
    )
    {
        $this->parentRelationsContext = $parentRelationsContext;
        $this->configurableOptionsProvider = $configurableOptionsProvider;
        $this->basePriceProvider = $basePriceProvider;
        $this->logger = $logger;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     */
    public function getData(
        array                      $products,
        FeedSpecificationInterface $feedSpecification
    ): array
    {
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
        array            $ignoredFields
    ): array
    {
        $parent = $this->parentRelationsContext->getParentsByChildId((int)$product->getId());
        if (!$parent instanceof ProductInterface) {
            return [];
        }
        $cacheKey = 'parent_' . (int)$parent->getId() . '_' . $parent->getTypeId();

        if (isset($this->cache[$cacheKey])) {
            $this->logger->debug(
                'MinMaxPricesProvider: Cached data found',
                [
                    'cacheKey' => $cacheKey,
                    'cacheData' => $this->cache[$cacheKey]
                ]
            );
            return $this->cache[$cacheKey];
        }

        $variants = $this->getParentChildren($parent);
        if (empty($variants)) {
            $this->logger->debug(
                'MinMaxPricesProvider: Empty variants not found',
                [
                    'cacheKey' => $cacheKey
                ]
            );
            return [];
        }

        $this->cache[$cacheKey] = $this->aggregatePrices(
            $variants,
            $ignoredFields
        );

        $this->logger->debug('MinMaxPricesProvider: Aggregated min/max prices for parent product',
            [
                'cacheKey' => $cacheKey,
                'cacheData' => $this->cache[$cacheKey],
                'variants' => array_keys($variants)
            ]
        );

        return $this->cache[$cacheKey];
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
    ): array
    {
        $regularPrices = [];
        $finalPrices = [];
        $maxPrices = [];

        foreach ($products as $product) {
            $prices = $this->basePriceProvider->getPrices($product, $ignoredFields);

            if (isset($prices[PricesProvider::REGULAR_PRICE_KEY])) {
                $regularPrices[] = (float)$prices[PricesProvider::REGULAR_PRICE_KEY];
            }

            if (isset($prices[PricesProvider::FINAL_PRICE_KEY])) {
                $finalPrices[] = (float)$prices[PricesProvider::FINAL_PRICE_KEY];
            }

            if (isset($prices[PricesProvider::MAX_PRICE_KEY])) {
                $maxPrices[] = (float)$prices[PricesProvider::MAX_PRICE_KEY];
            }
        }
        $this->logger->debug(
            'Aggregated prices for parent product',
            [
                'regularPrices' => $regularPrices,
                'finalPrices' => $finalPrices,
                'maxPrices' => $maxPrices,
            ]
        );

        return [
            'ss_minimums' => [
                PricesProvider::REGULAR_PRICE_KEY => !empty($regularPrices) ? min($regularPrices) : 0.0,

                PricesProvider::FINAL_PRICE_KEY => !empty($finalPrices) ? min($finalPrices) : 0.0,

                PricesProvider::MAX_PRICE_KEY => !empty($maxPrices) ? min($maxPrices) : 0.0,
            ],

            'ss_maximums' => [
                PricesProvider::REGULAR_PRICE_KEY => !empty($regularPrices) ? max($regularPrices) : 0.0,

                PricesProvider::FINAL_PRICE_KEY => !empty($finalPrices) ? max($finalPrices) : 0.0,

                PricesProvider::MAX_PRICE_KEY => !empty($maxPrices) ? max($maxPrices) : 0.0,
            ],
        ];
    }

    /**
     * @param ProductInterface $parent
     * @return array
     */
    private function getParentChildren(
        ProductInterface $parent
    ): array
    {
        switch ($parent->getTypeId()) {
            case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                return $this->configurableOptionsProvider->getProducts($parent);

            case \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE:
                return $parent->getTypeInstance()->getAssociatedProducts($parent);

            default:
                return [];
        }
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
