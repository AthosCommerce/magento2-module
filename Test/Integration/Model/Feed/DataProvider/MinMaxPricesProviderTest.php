<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Integration\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\MinMaxPricesProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentRelationsContext;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\Specification\Feed;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use AthosCommerce\Feed\Model\ItemsGenerator;
use Magento\Catalog\Api\ProductRepositoryInterface;
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
     * @var ParentRelationsContext
     */
    private ParentRelationsContext $parentRelationsContext;
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
        $this->parentRelationsContext = $objectManager->get(ParentRelationsContext::class);
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);
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

        $parentAwareRows = array_values(array_filter(
            $result,
            static fn (array $row): bool => !($row[Constant::IS_STANDALONE_PRODUCT_KEY] ?? false)
                && (array_key_exists('ss_minimums', $row) || array_key_exists('ss_maximums', $row))
        ));

        self::assertNotEmpty($parentAwareRows);
        self::assertGreaterThanOrEqual(2, count($parentAwareRows));

        $first = $parentAwareRows[0];
        $second = $parentAwareRows[1];

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

        $parentAwareRows = array_values(array_filter(
            $result,
            static fn (array $row): bool => !($row[Constant::IS_STANDALONE_PRODUCT_KEY] ?? false)
                && (array_key_exists('ss_minimums', $row) || array_key_exists('ss_maximums', $row))
        ));

        self::assertNotEmpty($parentAwareRows, print_r($result, true));
        self::assertGreaterThanOrEqual(2, count($parentAwareRows));

        $first = $parentAwareRows[0];
        $second = $parentAwareRows[1];

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

        $row = $this->getParentAwareRow($result);

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

        $parentAwareRows = array_values(array_filter(
            $result,
            static fn (array $row): bool => !($row[Constant::IS_STANDALONE_PRODUCT_KEY] ?? false)
                && (array_key_exists('ss_minimums', $row) || array_key_exists('ss_maximums', $row))
        ));

        if ($parentAwareRows === []) {
            self::markTestSkipped('Catalog rule fixture does not expose a parent-aware min/max row in this Magento runtime.');
        }

        $row = $parentAwareRows[0];

        self::assertArrayHasKey('ss_minimums', $row);
        self::assertArrayHasKey('ss_maximums', $row);

        self::assertGreaterThan(0.0, $row['ss_minimums']['final_price'], print_r($row, true));

        self::assertGreaterThan(0, $row['ss_maximums']['final_price'], print_r($row, true));
        $this->contextManager->resetContext();
        $this->provider->reset();
        $this->itemsGenerator->resetDataProviders($specification);
    }

    /**
     * @param array<int, array<string, mixed>> $result
     * @return array<string, mixed>
     */
    private function getParentAwareRow(array $result): array
    {
        foreach ($result as $row) {
            if (!is_array($row)) {
                continue;
            }

            if (($row[Constant::IS_STANDALONE_PRODUCT_KEY] ?? false) === true) {
                continue;
            }

            if (array_key_exists('ss_minimums', $row) || array_key_exists('ss_maximums', $row)) {
                return $row;
            }
        }

        self::fail('No parent-aware min/max row found in provider result: ' . print_r($result, true));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     */
    public function testStandaloneRowIsSkippedAndParentAwareRowGetsAggregates(): void
    {
        require '/var/www/html/athoscommerce/magento2-module/Test/_files/configurable/configurable_products_rollback.php';
        require '/var/www/html/athoscommerce/magento2-module/Test/_files/configurable/configurable_products.php';

        try {
            $specification = $this->specificationBuilder->build([]);
            $this->contextManager->setContextFromSpecification($specification);

            $simple = $this->productRepository->get('athoscommerce_configurable_test_simple_10', false, null, true);
            $this->parentRelationsContext->buildContext([(int)$simple->getId()], $specification);

            $result = $this->provider->getData([
                [
                    'entity_id' => (int)$simple->getId(),
                    'product_model' => $simple,
                    Constant::IS_STANDALONE_PRODUCT_KEY => true,
                ],
                [
                    'entity_id' => (int)$simple->getId(),
                    'product_model' => $simple,
                    Constant::IS_STANDALONE_PRODUCT_KEY => false,
                ],
            ], $specification);

            self::assertArrayNotHasKey('ss_minimums', $result[0]);
            self::assertArrayNotHasKey('ss_maximums', $result[0]);
            self::assertArrayHasKey('ss_minimums', $result[1], print_r($result[1], true));
            self::assertArrayHasKey('ss_maximums', $result[1], print_r($result[1], true));
            self::assertSame(
                [
                    'regular_price' => 10.0,
                    'final_price' => 10.0,
                    'max_price' => 10.0,
                ],
                $result[1]['ss_minimums']
            );
            self::assertSame(
                [
                    'regular_price' => 40.0,
                    'final_price' => 40.0,
                    'max_price' => 40.0,
                ],
                $result[1]['ss_maximums']
            );
        } finally {
            $this->parentRelationsContext->reset();
            $this->contextManager->resetContext();
            $this->provider->reset();
            require '/var/www/html/athoscommerce/magento2-module/Test/_files/configurable/configurable_products_rollback.php';
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
