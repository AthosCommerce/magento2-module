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

namespace AthosCommerce\Feed\Test\Integration\Model\Feed\DataProvider\Parent;

use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentRelationsContext;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ConfigurableDataProvider;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use AthosCommerce\Feed\Model\ItemsGenerator;
use AthosCommerce\Feed\Test\Integration\Model\Feed\DataProvider\GetProducts;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigurableDataProviderTest extends TestCase
{
    use ChildProductAssertionsTrait;

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
     * @var ConfigurableDataProvider
     */
    private $configurableDataProvider;
    /**
     * @var ContextManagerInterface
     */
    private $contextManager;
    /**
     * @var ItemsGenerator
     */
    private $itemsGenerator;
    /**
     * @var ParentRelationsContext|mixed
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
        $this->configurableDataProvider = $this->objectManager->get(ConfigurableDataProvider::class);
        $this->contextManager = $this->objectManager->get(ContextManagerInterface::class);
        $this->itemsGenerator = $this->objectManager->get(ItemsGenerator::class);
        $this->parentRelationsContext = $this->objectManager->get(ParentRelationsContext::class);

        parent::setUp();
    }


    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products_not_visible_individually.php
     *
     * @throws \Exception
     */
    public function testGetDataWithNotVisible(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $this->assertNotEmpty($products);
        $data = $this->configurableDataProvider->getData(
            $products,
            $specification
        );
        $this->assertEmpty($data);

        $this->configurableDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products.php
     *
     * @throws \Exception
     */
    public function testGetData(): void
    {
        $specification = $this->specificationBuilder->build([
            'includeChildPrices' => true
        ]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);

        $data = $this->itemsGenerator->generate(
            $items,
            $specification
        );

        $config = [
            'products' => [
                'athoscommerce_configurable_test_configurable' => [
                    'child_count' => 4,
                    'sku_prefix' => 'athoscommerce_configurable_test_simple_',
                    'name_prefix' => 'AthosCommerce Test',
                ],
                'athoscommerce_configurable_test_configurable_2_attributes' => [
                    'child_count' => 2,
                    'sku_prefix' => 'athoscommerce_configurable_test_simple_',
                    'name_prefix' => 'AthosCommerce Test 2 Attributes'
                ]
            ],
            'required_attributes' => ['child_sku', 'child_sku', 'child_final_price']
        ];
        $this->assertChildProducts($data, $config);
        $this->configurableDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_boolean_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_decimal_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products.php
     *
     * @throws \Exception
     */
    public function testGetDataWithAdditionalAttributes(): void
    {
        $specification = $this->specificationBuilder->build([
            'includeChildPrices' => true,
            'childFields' => ['boolean_attribute', 'decimal_attribute']
        ]);
        $products = $this->getProducts->get($specification);
        $data = $this->configurableDataProvider->getData($products, $specification);
        $config = [
            'products' => [
                'athoscommerce_configurable_test_configurable' => [
                    'child_count' => 4,
                    'sku_prefix' => 'athoscommerce_configurable_test_simple_',
                    'name_prefix' => 'AthosCommerce Test',
                    'value_map' => [
                        'decimal_attribute' => ['10.000000', '20.000000', '30.000000', '40.000000'],
                        'boolean_attribute' => ['Yes', 'Yes', 'Yes', 'Yes']
                    ]
                ],
                'athoscommerce_configurable_test_configurable_2_attributes' => [
                    'child_count' => 2,
                    'sku_prefix' => 'athoscommerce_configurable_test_simple_',
                    'name_prefix' => 'AthosCommerce Test 2 Attributes',
                    'value_map' => [
                        'decimal_attribute' => ['50.000000', '60.000000'],
                        'boolean_attribute' => ['Yes', 'Yes']
                    ]
                ]
            ],
            'required_attributes' => ['child_sku', 'child_name', 'child_final_price'],
            'additional_attributes' => ['boolean_attribute', 'decimal_attribute']
        ];

        $this->assertChildProducts($data, $config);
        $this->configurableDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products.php
     *
     * @throws \Exception
     */
    public function testGetDataWithoutChildPrice(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $data = $this->configurableDataProvider->getData($products, $specification);
        $config = [
            'products' => [
                'athoscommerce_configurable_test_configurable' => [
                    'child_count' => 4,
                    'sku_prefix' => 'athoscommerce_configurable_test_simple_',
                    'name_prefix' => 'AthosCommerce Test',
                ],
                'athoscommerce_configurable_test_configurable_2_attributes' => [
                    'child_count' => 2,
                    'sku_prefix' => 'athoscommerce_configurable_test_simple_',
                    'name_prefix' => 'AthosCommerce Test 2 Attributes'
                ]
            ],
            'required_attributes' => ['child_sku', 'child_name'],
            'restricted_attributes' => ['child_final_price']
        ];

        $this->assertChildProducts($data, $config);
        $this->configurableDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products_with_store_value.php
     *
     * @throws \Exception
     */
    public function testGetDataWithMultistoreValues(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);
        $products = $this->getProducts->get($specification);
        $data = $this->configurableDataProvider->getData($products, $specification);
        $config = [
            'products' => [
                'athoscommerce_configurable_test_configurable' => [
                    'child_count' => 4,
                    'sku_prefix' => 'athoscommerce_configurable_test_simple_',
                    'name_prefix' => 'Store Default AthosCommerce Test',
                ],
                'athoscommerce_configurable_test_configurable_2_attributes' => [
                    'child_count' => 2,
                    'sku_prefix' => 'athoscommerce_configurable_test_simple_',
                    'name_prefix' => 'Store Default AthosCommerce Test 2 Attributes'
                ]
            ],
            'required_attributes' => ['child_sku', 'child_name'],
        ];

        $this->assertChildProducts($data, $config);
        $this->contextManager->resetContext();
        $this->configurableDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products_disabled_simple.php
     *
     * @throws \Exception
     */
    public function testGetDataWithDisabledSimples(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $data = $this->configurableDataProvider->getData($products, $specification);
        foreach ($data as $product) {
            $this->assertArrayNotHasKey('child_sku', $product);
            $this->assertArrayNotHasKey('child_name', $product);
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products_not_visible_ind_child_with_any.php
     *
     * @throws \Exception
     */
    public function testGetDataWithParentSetToNVIAndChildSetToAny(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);
        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate(
            $items,
            $specification
        );
        $this->assertNotEmpty($data);

        foreach ($data as $product) {
            $this->assertArrayNotHasKey('__parent_id', $product);
            $this->assertArrayNotHasKey('__parent_title', $product);
            $this->assertArrayNotHasKey('parent_status', $product);
            $this->assertArrayNotHasKey('parent_type_id', $product);
            $this->assertArrayNotHasKey('parent_visibility', $product);

            $this->assertContains(
                $product['visibility'],
                [
                    'Catalog, Search',
                    'Catalog',
                    'Search',
                ]
            );
        }
        $this->contextManager->resetContext();
        $this->configurableDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products_visibility_any_child_visibility_any.php
     *
     * @throws \Exception
     */
    public function testGetDataWithParentSetToAnyWhenChildSetToAny(): void
    {
        $specification = $this->specificationBuilder->build(
            [
                'include_menu_categories' => true,
                'include_url_hierarchy' => true,
            ]
        );
        $this->contextManager->setContextFromSpecification($specification);
        $items = $this->getProducts->getCollectionItems($specification);

        $data = $this->itemsGenerator->generate(
            $items,
            $specification
        );

        /**
         * 4 products simple
         * 4 products configurable'variants
         */
        $this->assertCount(8, $data);

        $this->assertNotEmpty($data);

        $standaloneProducts = [];
        $variantProducts = [];

        foreach ($data as $product) {
            if (isset($product['__parent_id'])) {
                $variantProducts[] = $product;
            } else {
                $standaloneProducts[] = $product;
            }
        }
        $this->assertCount(4, $standaloneProducts, 'Should have 4 standalone simple products');
        $this->assertCount(4, $variantProducts, 'Should have 4 configurable variant products');

        foreach ($standaloneProducts as $product) {
            $this->assertArrayNotHasKey('__parent_id', $product, 'Standalone product should not have __parent_id');
            $this->assertArrayNotHasKey('__parent_title', $product, 'Standalone product should not have __parent_title');
            $this->assertArrayNotHasKey('parent_status', $product, 'Standalone product should not have parent_status');
            $this->assertArrayNotHasKey('parent_type_id', $product, 'Standalone product should not have parent_type_id');
            $this->assertArrayNotHasKey('parent_url', $product, 'Standalone product should not have parent_url');
            $this->assertArrayNotHasKey('parent_visibility', $product, 'Standalone product should not have parent_visibility');

            $this->assertContains(
                $product['visibility'],
                ['Catalog, Search', 'Catalog', 'Search'],
                'Standalone product visibility should be valid'
            );

            $this->assertStringStartsWith('AthosCommerce Test Configurable Option', $product['name']);
            $this->assertStringStartsWith('athos_config_test_simple', $product['sku']);

        }

        foreach ($variantProducts as $product) {
            $this->assertArrayHasKey('__parent_id', $product, 'Variant should have __parent_id');
            $this->assertArrayHasKey('__parent_title', $product, 'Variant should have __parent_title');
            $this->assertArrayHasKey('parent_status', $product, 'Variant should have parent_status');
            $this->assertArrayHasKey('parent_type_id', $product, 'Variant should have parent_type_id');
            $this->assertArrayHasKey('parent_url', $product, 'Variant should have parent_url');
            $this->assertArrayHasKey('parent_visibility', $product, 'Variant should have parent_visibility');

            $this->assertStringStartsWith('AthosCommerce Configurable Product Test CATALOG', $product['__parent_title']);
            $this->assertEquals('Enabled', $product['parent_status']);
            $this->assertEquals('configurable', $product['parent_type_id']);
            $this->assertEquals('Catalog, Search', $product['parent_visibility']);
            $this->assertStringContainsString('athoscommerce-configurable-product-test-catalog', $product['parent_url']);

            $this->assertContains(
                $product['visibility'],
                ['Catalog, Search', 'Catalog', 'Search'],
                'Variant product visibility should be valid'
            );
        }


        $this->contextManager->resetContext();
        $this->configurableDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products.php
     *
     * @throws \Exception
     */
    public function testReset(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $this->configurableDataProvider->getData($products, $specification);
        $this->configurableDataProvider->reset();
        $this->assertTrue(true);
    }

}
