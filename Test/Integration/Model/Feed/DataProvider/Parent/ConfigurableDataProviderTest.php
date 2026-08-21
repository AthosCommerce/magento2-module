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
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ConfigurableDataProvider;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\Resolver\RowResolverPool;
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
     * @var RowResolverPool|mixed
     */
    private $rowResolverPool;

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
        $this->rowResolverPool = $this->objectManager->get(RowResolverPool::class);

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

            $this->assertStringStartsWith(
                'AthosCommerce Configurable Product Test CATALOG',
                $product['name']
            );
            $this->assertStringStartsWith(
                'Catalog, Search',
                $product['visibility']
            );
            $this->assertStringContainsString(
                'athoscommerce-configurable-product-test-catalog',
                $product['url']
            );
            $this->assertStringStartsWith(
                'configurable',
                $product['type_id']
            );
            $this->assertStringStartsWith(
                'DESC: AthosCommerce Test Configurable',
                $product['description']
            );
            $this->assertStringStartsWith(
                'SHORT_DESC: AthosCommerce Test Configurable',
                $product['short_description']
            );

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

    /**
     * Test that __parent_id and __parent_sku are correctly set for standalone products.
     *
     * For standalone products (visible individually, not belonging to parent):
     * - __parent_id should be based on parentIdSourceFieldName configuration (defaults to entity_id)
     * - __parent_sku should equal the product's sku
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products.php
     *
     * @throws \Exception
     */
    public function testStandaloneProductsParentIdAndSku(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        $this->assertNotEmpty($data, 'Data should not be empty');

        foreach ($data as $product) {
            if (isset($product['___standalone_product']) && $product['___standalone_product'] === true) {
                // This is a standalone simple product
                $entityId = (string)($product['entity_id'] ?? '');
                $sku = (string)($product['sku'] ?? '');
                $parentId = (string)($product['__parent_id'] ?? '');
                $parentSku = (string)($product['__parent_sku'] ?? '');

                $this->assertNotEmpty($entityId, 'entity_id should not be empty for standalone product');
                $this->assertNotEmpty($sku, 'sku should not be empty for standalone product');

                // Parent ID should be populated (by default uses entity_id)
                $this->assertNotEmpty(
                    $parentId,
                    sprintf(
                        'Standalone product %s: __parent_id should not be empty',
                        $sku
                    )
                );

                // __parent_sku should equal the product's sku
                $this->assertEquals(
                    $sku,
                    $parentSku,
                    sprintf(
                        'Standalone product %s: __parent_sku should equal sku. Got %s, expected %s',
                        $sku,
                        $parentSku,
                        $sku
                    )
                );
            }
        }

        $this->contextManager->resetContext();
        $this->configurableDataProvider->reset();
    }

    /**
     * Test that __parent_id and __parent_sku are correctly set for child products.
     *
     * For child products (belonging to configurable parent):
     * - __parent_id should equal the parent's entity_id (NOT the child's entity_id)
     * - __parent_sku should equal the parent's sku (NOT the child's sku)
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products.php
     *
     * @throws \Exception
     */
    public function testChildProductsParentIdAndSku(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        $this->assertNotEmpty($data, 'Data should not be empty');

        foreach ($data as $product) {
            if (isset($product['__is_belong_to_parent']) && $product['__is_belong_to_parent'] === true) {
                // This is a child product belonging to a parent
                $entityId = (string)($product['entity_id'] ?? '');
                $sku = (string)($product['sku'] ?? '');
                $childSku = (string)($product['child_sku'] ?? '');
                $parentId = (string)($product['__parent_id'] ?? '');
                $parentSku = (string)($product['__parent_sku'] ?? '');
                $parentTitle = (string)($product['__parent_title'] ?? '');

                $this->assertNotEmpty($entityId, 'entity_id should not be empty for child product');
                $this->assertNotEmpty($sku, 'sku should not be empty for child product');
                $this->assertNotEmpty($parentId, '__parent_id should not be empty for child product');
                $this->assertNotEmpty($parentSku, '__parent_sku should not be empty for child product');
                $this->assertNotEmpty($parentTitle, '__parent_title should not be empty for child product');

                // Parent ID should be different from child entity_id
                $this->assertNotEquals(
                    $entityId,
                    $parentId,
                    sprintf(
                        'Child product %s: __parent_id should be parent\'s entity_id, not child\'s entity_id (%s)',
                        $sku,
                        $entityId
                    )
                );

                // Parent SKU should be different from child sku
                $this->assertNotEquals(
                    $sku,
                    $parentSku,
                    sprintf(
                        'Child product %s: __parent_sku should be parent\'s sku, not child\'s sku',
                        $sku
                    )
                );

                // Verify that parent_id is numeric (valid entity_id)
                $this->assertTrue(
                    is_numeric($parentId) || ctype_digit((string)$parentId),
                    sprintf(
                        'Child product %s: __parent_id should be numeric. Got %s',
                        $sku,
                        $parentId
                    )
                );
            }
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
    public function testBlankParentIdSourceUsesEntityIdSpaceForConfigurableRows(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        $this->assertNotEmpty($data, 'Data should not be empty');

        $assertedParentRows = 0;
        $assertedStandaloneRows = 0;

        foreach ($data as $product) {
            if (($product[Constant::IS_BELONG_TO_PARENT_KEY] ?? false) === true
                && ($product[Constant::RESOLVED_PARENT_ROW_SOURCE_KEY] ?? '') === 'configurable'
            ) {
                $parentId = (string)($product[Constant::PARENT_ID] ?? '');
                $resolvedParentId = (string)($product[Constant::RESOLVED_PARENT_ID_KEY] ?? '');

                $this->assertNotSame('', $parentId, Constant::PARENT_ID . ' should not be empty for configurable row');
                $this->assertNotSame('', $resolvedParentId, Constant::RESOLVED_PARENT_ID_KEY . ' should not be empty');
                $this->assertSame(
                    $resolvedParentId,
                    $parentId,
                    'Configurable child row should use parent entity_id in __parent_id when parentIdSourceFieldName is blank'
                );
                $assertedParentRows++;
            }

            if (($product[Constant::IS_STANDALONE_PRODUCT_KEY] ?? false) === true) {
                $entityId = (string)($product['entity_id'] ?? '');
                $parentId = (string)($product[Constant::PARENT_ID] ?? '');

                $this->assertNotSame('', $entityId, 'Standalone row entity_id should not be empty');
                $this->assertNotSame('', $parentId, 'Standalone row __parent_id should not be empty');
                $this->assertSame(
                    $entityId,
                    $parentId,
                    'Standalone row should use its own entity_id in __parent_id when parentIdSourceFieldName is blank'
                );
                $assertedStandaloneRows++;
            }
        }

        $this->assertGreaterThan(0, $assertedParentRows, 'Expected configurable parent-context rows to be asserted');
        $this->assertGreaterThan(0, $assertedStandaloneRows, 'Expected standalone rows to be asserted');

        $this->contextManager->resetContext();
        $this->configurableDataProvider->reset();
    }

}
