<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Integration\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\MinMaxPricesProvider;
use AthosCommerce\Feed\Model\Feed\Specification\Feed;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use AthosCommerce\Feed\Model\ItemsGenerator;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class MinMaxPricesProviderTest extends TestCase
{
    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;
    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;
    /**
     * @var GetProducts
     */
    private $getProducts;
    /**
     * @var MinMaxPricesProvider
     */
    private MinMaxPricesProvider $provider;
    /**
     * @var FeedSpecificationInterface
     */
    private FeedSpecificationInterface $feedSpecification;
    /**
     * @var ContextManagerInterface
     */
    private $contextManager;
    /**
     * @var ItemsGenerator
     */
    private $itemsGenerator;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $this->specificationBuilder = $objectManager->get(SpecificationBuilderInterface::class);
        $this->contextManager = $objectManager->get(ContextManagerInterface::class);
        $this->getProducts = $objectManager->get(GetProducts::class);
        $this->provider = $objectManager->create(MinMaxPricesProvider::class);
        $this->itemsGenerator = $objectManager->get(ItemsGenerator::class);
        $this->feedSpecification = $objectManager->create(Feed::class);
        parent::setUp();
    }

    /**
     * Validates:
     * - configurable child variants generate ss_minimums/ss_maximums
     * - all siblings share same aggregation
     * - min/max structure exists
     * - minimums <= maximums
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products.php
     *
     * @throws Exception
     */
    public function testConfigurableVariantsGenerateMinMaxPrices(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);
        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);
        $result = $this->provider->getData($data, $specification);

        self::assertCount(2, $result);

        $first = $result[0];
        $second = $result[1];

        self::assertArrayHasKey('ss_minimums', $first, print_r($first, true));
        self::assertArrayHasKey('ss_maximums', $first, print_r($first, true));

        self::assertArrayHasKey('ss_minimums', $second, print_r($second, true));
        self::assertArrayHasKey('ss_maximums', $second, print_r($second, true));

        self::assertEquals($first['ss_minimums'], $second['ss_minimums'], print_r($second, true));
        self::assertEquals($first['ss_maximums'], $second['ss_maximums'], print_r($second, true));

        self::assertLessThanOrEqual($first['ss_maximums']['regular_price'], $first['ss_minimums']['regular_price']);
        self::assertLessThanOrEqual($first['ss_maximums']['final_price'], $first['ss_minimums']['final_price']);
        self::assertLessThanOrEqual($first['ss_maximums']['max_price'], $first['ss_minimums']['max_price']);

        $this->contextManager->resetContext();
        $this->itemsGenerator->resetDataProviders($specification);
    }

    /**
     * Validates:
     * - grouped child variants generate ss_minimums/ss_maximums
     * - grouped children share same aggregation
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products.php
     *
     * @throws Exception
     */
    public function testGroupedVariantsGenerateMinMaxPrices(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);
        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);
        $result = $this->provider->getData($data, $specification);

        self::assertCount(6, $result, print_r($result, true));

        $first = $result[0];
        $second = $result[1];

        self::assertArrayHasKey('ss_minimums', $first, print_r($result, true));
        self::assertArrayHasKey('ss_maximums', $first, print_r($result, true));

        self::assertArrayHasKey('ss_minimums', $second, print_r($result, true));
        self::assertArrayHasKey('ss_maximums', $second, print_r($result, true));

        self::assertEquals($first['ss_minimums'], $second['ss_minimums'], print_r($result, true));

        self::assertEquals($first['ss_maximums'], $second['ss_maximums'], print_r($result, true));
        $this->contextManager->resetContext();
        $this->provider->reset();
        $this->itemsGenerator->resetDataProviders($specification);
    }

    /**
     * Validates:
     * - orphan simple products do not generate ss_minimums
     * - orphan simple products do not generate ss_maximums
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/02_simple_products_diff_prices.php
     *
     * @throws Exception
     */
    public function testOrphanSimpleDoesNotGenerateMinMaxPrices(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);
        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);
        $result = $this->provider->getData($data, $specification);

        self::assertCount(2, $result, print_r($result, true));

        $row = current($result);

        self::assertArrayNotHasKey('ss_minimums', $row, print_r($row, true));
        self::assertArrayNotHasKey('ss_maximums', $row, print_r($row, true));
        $this->contextManager->resetContext();
        $this->provider->reset();
        $this->itemsGenerator->resetDataProviders($specification);
    }

    /**
     * Validates:
     * - special pricing aggregation
     * - configurable variant aggregation
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products_specialprice.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products_specialprice.php
     *
     * @throws Exception
     */
    public function testConfigurableSpecialPriceAggregation(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);
        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);
        $result = $this->provider->getData($data, $specification);

        $row = current($result);

        self::assertArrayHasKey('ss_minimums', $row);
        self::assertArrayHasKey('ss_maximums', $row);

        self::assertLessThanOrEqual($row['ss_maximums']['final_price'], $row['ss_minimums']['final_price'], print_r($row, true));
        $this->contextManager->resetContext();
        $this->provider->reset();
        $this->itemsGenerator->resetDataProviders($specification);
    }

    /**
     * Validates:
     * - catalog rule pricing aggregation
     * - configurable variant contextual aggregation
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products_catalogrule.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products_catalogrule.php
     *
     * @throws Exception
     */
    public function testCatalogRuleAggregation(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);
        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);
        $result = $this->provider->getData($data, $specification);

        $row = current($result);

        self::assertArrayHasKey('ss_minimums', $row);
        self::assertArrayHasKey('ss_maximums', $row);

        self::assertGreaterThan(0.0, $row['ss_minimums']['final_price'], print_r($row, true));

        self::assertGreaterThan(0, $row['ss_maximums']['final_price'], print_r($row, true));
        $this->contextManager->resetContext();
        $this->provider->reset();
        $this->itemsGenerator->resetDataProviders($specification);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
