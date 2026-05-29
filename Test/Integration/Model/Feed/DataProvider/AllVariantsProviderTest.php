<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Integration\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\AllVariantsProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentRelationsContext;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use AthosCommerce\Feed\Test\Integration\Model\Feed\DataProvider\GetProducts;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AllVariantsProviderTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;

    /**
     * @var GetProducts
     */
    private $getProducts;

    /**
     * @var AllVariantsProvider
     */
    private $allVariantsProvider;

    /**
     * @var ContextManagerInterface
     */
    private $contextManager;

    /**
     * @var ParentRelationsContext
     */
    private $parentRelationsContext;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);

        $this->getProducts = $this->objectManager->get(GetProducts::class);

        $this->allVariantsProvider = $this->objectManager->get(AllVariantsProvider::class);

        $this->contextManager = $this->objectManager->get(ContextManagerInterface::class);

        $this->parentRelationsContext = $this->objectManager->get(ParentRelationsContext::class);

        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products.php
     *
     * @throws \Exception
     */
    public function testGetDataForGroupedProducts(): void
    {
        $specification = $this->specificationBuilder->build([
            'includeAllVariants' => true
        ]);

        $products = $this->getProducts->get($specification);

        $childIds = [];

        foreach ($products as $product) {
            $productModel = $product['product_model'] ?? null;
            if (in_array($productModel->getTypeId(), ['simple', 'virtual'], true)) {
                $childIds[] = (int)$productModel->getId();
            }
        }

        $this->parentRelationsContext->buildContext(
            $childIds,
            $specification
        );

        $data = $this->allVariantsProvider->getData(
            $products,
            $specification
        );

        $this->assertNotEmpty($data);

        $groupedProductsFound = 0;

        foreach ($data as $row) {

            if (!isset($row['__all_variants'])) {
                continue;
            }

            if (empty($row['__all_variants'])) {
                continue;
            }

            $variants = $row['__all_variants'];

            $this->assertIsArray($variants);

            foreach ($variants as $variant) {

                $this->assertArrayHasKey('mappings', $variant);
                $this->assertArrayHasKey('options', $variant);
                $this->assertArrayHasKey('attributes', $variant);

                $this->assertArrayHasKey('core', $variant['mappings']);

                $this->assertArrayHasKey('uid', $variant['mappings']['core']);

                $this->assertArrayHasKey('price', $variant['mappings']['core']);

                $this->assertArrayHasKey('inventory_quantity', $variant['attributes']);

                $this->assertArrayHasKey('title', $variant['attributes']);

                $this->assertArrayHasKey('sku', $variant['attributes']);
            }

            $groupedProductsFound++;
        }

        $this->assertGreaterThan(0, $groupedProductsFound);

        $this->allVariantsProvider->reset();
        $this->parentRelationsContext->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products.php
     *
     * @throws \Exception
     */
    public function testGetDataForConfigurableProducts(): void
    {
        $specification = $this->specificationBuilder->build([
            'includeAllVariants' => true
        ]);

        $products = $this->getProducts->get($specification);

        $childIds = [];

        foreach ($products as $product) {
            $productModel = $product['product_model'] ?? null;
            if (in_array($productModel->getTypeId(), ['simple', 'virtual'], true)) {
                $childIds[] = (int)$productModel->getId();
            }
        }

        $this->parentRelationsContext->buildContext(
            $childIds,
            $specification
        );

        $data = $this->allVariantsProvider->getData(
            $products,
            $specification
        );

        $this->assertNotEmpty($data);

        $configurableVariantsFound = 0;

        foreach ($data as $row) {

            if (empty($row['__all_variants'])) {
                continue;
            }

            foreach ($row['__all_variants'] as $variant) {

                $this->assertIsArray($variant['options']);

                if (!empty($variant['options'])) {

                    foreach ($variant['options'] as $attributeCode => $option) {

                        $this->assertNotEmpty($attributeCode);

                        $this->assertArrayHasKey('value', $option);
                    }

                    $configurableVariantsFound++;
                }
            }
        }

        $this->assertGreaterThan(0, $configurableVariantsFound);

        $this->allVariantsProvider->reset();
        $this->parentRelationsContext->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     *
     * @throws \Exception
     */
    public function testGetDataWithoutParents(): void
    {
        $specification = $this->specificationBuilder->build([
            'includeAllVariants' => true
        ]);

        $products = $this->getProducts->get($specification);

        $data = $this->allVariantsProvider->getData(
            $products,
            $specification
        );

        foreach ($data as $row) {

            if (!isset($row['__all_variants'])) {
                continue;
            }

            $this->assertEquals([], $row['__all_variants']);
        }

        $this->allVariantsProvider->reset();
    }
}
