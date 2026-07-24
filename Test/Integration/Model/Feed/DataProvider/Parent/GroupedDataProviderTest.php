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

use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\GroupedDataProvider;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use AthosCommerce\Feed\Model\ItemsGenerator;
use AthosCommerce\Feed\Test\Integration\Model\Feed\DataProvider\GetProducts;
use Magento\Catalog\Model\Product;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GroupedDataProviderTest extends TestCase
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
     * @var GroupedDataProvider
     */
    private $groupedDataProvider;

    /**
     * @var ContextManagerInterface
     */
    private $contextManager;

    /**
     * @var ItemsGenerator
     */
    private $itemsGenerator;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->getProducts = $this->objectManager->get(GetProducts::class);
        $this->groupedDataProvider = $this->objectManager->get(GroupedDataProvider::class);
        $this->contextManager = $this->objectManager->get(ContextManagerInterface::class);
        $this->itemsGenerator = $this->objectManager->get(ItemsGenerator::class);

        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products_nvi.php
     *
     * @throws \Exception
     */
    public function testGetDataWithNotVisible(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        $exportedSkus = [];
        foreach ($data as $row) {
            $sku = (string)($row['sku'] ?? '');
            if ($sku !== '') {
                $exportedSkus[] = $sku;
            }
        }

        $this->assertNotContains('athoscommerce_grouped_test_grouped_1', $exportedSkus);
        $this->assertNotContains('athoscommerce_grouped_test_grouped_2', $exportedSkus);

        $this->assertNotContains('athoscommerce_grouped_test_simple_1000', $exportedSkus);
        $this->assertNotContains('athoscommerce_grouped_test_simple_1001', $exportedSkus);

        $this->assertContains('athoscommerce_grouped_test_simple_1010', $exportedSkus);
        $this->assertContains('athoscommerce_grouped_test_simple_1011', $exportedSkus);
        $this->assertContains('athoscommerce_grouped_test_simple_1012', $exportedSkus);
        $this->assertContains('athoscommerce_grouped_test_simple_1013', $exportedSkus);

        $this->groupedDataProvider->reset();
        $this->contextManager->resetContext();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products.php
     *
     * @throws \Exception
     */
    public function testGetData(): void
    {
        $specification = $this->specificationBuilder->build(['includeChildPrices' => true]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        $config = [
            'products' => [
                'athoscommerce_grouped_test_grouped_1' => [
                    'child_count' => 2,
                    'sku_prefix' => 'athoscommerce_grouped_test_simple_',
                    'name_prefix' => 'AthosCommerce Grouped Test Simple',
                ],
                'athoscommerce_grouped_test_grouped_2' => [
                    'child_count' => 4,
                    'sku_prefix' => 'athoscommerce_grouped_test_simple_',
                    'name_prefix' => 'AthosCommerce Grouped 2 Test Simple'
                ]
            ],
            'required_attributes' => ['child_sku', 'child_final_price']
        ];

        $this->assertChildProducts($data, $config);

        $this->contextManager->resetContext();
        $this->groupedDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_boolean_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_decimal_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products.php
     *
     * @throws \Exception
     */
    public function testGetDataWithAdditionalAttributes(): void
    {
        $specification = $this->specificationBuilder->build([
            'includeChildPrices' => true,
            'childFields' => ['boolean_attribute', 'decimal_attribute']
        ]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        $config = [
            'products' => [
                'athoscommerce_grouped_test_grouped_1' => [
                    'child_count' => 2,
                    'sku_prefix' => 'athoscommerce_grouped_test_simple_',
                    'name_prefix' => 'AthosCommerce Grouped Test Simple',
                    'value_map' => [
                        'decimal_attribute' => ['1000.000000', '1001.000000'],
                        'boolean_attribute' => ['Yes', 'Yes']
                    ]
                ],
                'athoscommerce_grouped_test_grouped_2' => [
                    'child_count' => 4,
                    'sku_prefix' => 'athoscommerce_grouped_test_simple_',
                    'name_prefix' => 'AthosCommerce Grouped 2 Test Simple',
                    'value_map' => [
                        'decimal_attribute' => ['1010.000000', '1011.000000', '1012.000000', '1013.000000'],
                        'boolean_attribute' => ['No', 'No', 'No', 'No']
                    ]
                ]
            ],
            'required_attributes' => ['child_sku', 'child_name', 'child_final_price'],
            'additional_attributes' => ['boolean_attribute', 'decimal_attribute']
        ];

        $this->assertChildProducts($data, $config);

        $this->contextManager->resetContext();
        $this->groupedDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products_with_store_value.php
     *
     * @throws \Exception
     */
    public function testGetDataWithMultistoreValues(): void
    {
        $specification = $this->specificationBuilder->build(['includeChildPrices' => true]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        $config = [
            'products' => [
                'athoscommerce_grouped_test_grouped_1' => [
                    'child_count' => 2,
                    'sku_prefix' => 'athoscommerce_grouped_test_simple_',
                    'name_prefix' => 'Store Default AthosCommerce Grouped Test Simple'
                ],
                'athoscommerce_grouped_test_grouped_2' => [
                    'child_count' => 4,
                    'sku_prefix' => 'athoscommerce_grouped_test_simple_',
                    'name_prefix' => 'Store Default AthosCommerce Grouped 2 Test Simple'
                ]
            ],
            'required_attributes' => ['child_sku', 'child_name', 'child_final_price']
        ];

        $this->assertChildProducts($data, $config);

        $this->contextManager->resetContext();
        $this->groupedDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products_disabled_simple.php
     *
     * @throws \Exception
     */
    public function testGetDataWithDisabledSimples(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        $exportedSkus = [];
        foreach ($data as $row) {
            $sku = (string)($row['sku'] ?? '');
            if ($sku !== '') {
                $exportedSkus[] = $sku;
            }
        }

        $this->assertNotContains('athoscommerce_grouped_test_disabled_simple_1030', $exportedSkus);
        $this->assertNotContains('athoscommerce_grouped_test_disabled_simple_1031', $exportedSkus);
        $this->assertNotContains('athoscommerce_grouped_test_disabled_simple_1032', $exportedSkus);
        $this->assertNotContains('athoscommerce_grouped_test_disabled_simple_1033', $exportedSkus);

        $this->contextManager->resetContext();
        $this->groupedDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products.php
     *
     * @throws \Exception
     */
    public function testVisibleChildIsExportedAsStandalone(): void
    {
        $specification = $this->specificationBuilder->build(['includeChildPrices' => true]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        $exportedSkus = [];
        foreach ($data as $row) {
            $sku = (string)($row['sku'] ?? '');
            if ($sku !== '') {
                $exportedSkus[] = $sku;
            }
        }

        $this->assertContains('athoscommerce_grouped_test_simple_1000', $exportedSkus);

        $this->contextManager->resetContext();
        $this->groupedDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products.php
     *
     * @throws \Exception
     */
    public function testStandaloneRowDoesNotContainParentFields(): void
    {
        $specification = $this->specificationBuilder->build(['includeChildPrices' => true]);
        $products = $this->getProducts->get($specification);
        $data = $this->groupedDataProvider->getData($products, $specification);

        $targetChildSku = 'athoscommerce_grouped_test_simple_1000';
        $standaloneRow = null;

        foreach ($data as $row) {
            /** @var Product|null $productModel */
            $productModel = $row['product_model'] ?? null;
            if (!$productModel instanceof Product) {
                continue;
            }

            if ((string)$productModel->getSku() !== $targetChildSku) {
                continue;
            }

            if (($row[Constant::IS_STANDALONE_PRODUCT_KEY] ?? false) === true) {
                $standaloneRow = $row;
                break;
            }
        }

        $this->assertNotNull($standaloneRow, 'Expected standalone row for visible child SKU: ' . $targetChildSku);

        $this->assertArrayNotHasKey(Constant::PARENT_ID, $standaloneRow);
        $this->assertArrayNotHasKey(Constant::PARENT_TITLE, $standaloneRow);
        $this->assertArrayNotHasKey(Constant::PARENT_SKU, $standaloneRow);
        $this->assertArrayNotHasKey(Constant::PARENT_IMAGE, $standaloneRow);
        $this->assertArrayNotHasKey(Constant::PARENT_STATUS, $standaloneRow);
        $this->assertArrayNotHasKey(Constant::PARENT_TYPE, $standaloneRow);
        $this->assertArrayNotHasKey(Constant::PARENT_URL, $standaloneRow);
        $this->assertArrayNotHasKey(Constant::PARENT_VISIBILITY, $standaloneRow);

        $this->groupedDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products_nvi.php
     *
     * @throws \Exception
     */
    public function testNotVisibleChildIsExportedOnlyInParentContextRows(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        foreach ($data as $row) {
            /** @var Product|null $productModel */
            $productModel = $row['product_model'] ?? null;
            if (!$productModel instanceof Product) {
                continue;
            }

            $sku = (string)$productModel->getSku();

            if (strpos($sku, 'athoscommerce_grouped_test_simple_') !== 0) {
                continue;
            }

            $this->assertNotEmpty(
                $row[Constant::IS_BELONG_TO_PARENT_KEY] ?? false,
                'NVI child should never be returned as a final standalone exported row. SKU: ' . $sku
            );

            $this->assertArrayHasKey(Constant::PARENT_SKU, $row, 'Expected grouped parent metadata for child SKU: ' . $sku);
        }

        $this->contextManager->resetContext();
        $this->groupedDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products_shared_child_multistore.php
     *
     * @throws \Exception
     */
    public function testSharedVisibleChildExportsTwoParentRowsAndOneStandaloneRow(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        $sharedChildSku = 'athoscommerce_grouped_shared_child_1';
        $parentSku1 = 'athoscommerce_grouped_shared_parent_1';
        $parentSku2 = 'athoscommerce_grouped_shared_parent_2';

        $matchingRows = [];
        foreach ($data as $row) {
            $sku = (string)($row['sku'] ?? '');
            $childSku = (string)($row['child_sku'] ?? '');

            if ($sku === $sharedChildSku || $childSku === $sharedChildSku) {
                $matchingRows[] = $row;
            }
        }

        $this->assertCount(3, $matchingRows, 'Expected exactly 3 exported rows for shared visible child.');

        $exportedSkus = array_map(static function (array $row): string {
            return (string)($row['sku'] ?? '');
        }, $matchingRows);

        $this->assertContains($sharedChildSku, $exportedSkus, 'Expected standalone child export row.');
        $this->assertContains($parentSku1, $exportedSkus, 'Expected grouped parent export row for parent 1.');
        $this->assertContains($parentSku2, $exportedSkus, 'Expected grouped parent export row for parent 2.');

        $parentRows = array_values(array_filter($matchingRows, static function (array $row) use ($sharedChildSku): bool {
            return (string)($row['sku'] ?? '') !== $sharedChildSku
                && (string)($row['child_sku'] ?? '') === $sharedChildSku;
        }));

        $this->assertCount(2, $parentRows, 'Expected 2 grouped parent-context rows for shared child.');

        $standaloneRows = array_values(array_filter($matchingRows, static function (array $row) use ($sharedChildSku): bool {
            return (string)($row['sku'] ?? '') === $sharedChildSku;
        }));

        $this->assertCount(1, $standaloneRows, 'Expected 1 standalone export row for shared child.');

        $this->contextManager->resetContext();
        $this->groupedDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/store/fixturestore.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products_shared_child_multistore.php
     * @magentoConfigFixture current_store web/seo/use_rewrites 1
     * @magentoConfigFixture current_store web/unsecure/base_url https://default.url/
     * @magentoConfigFixture current_store web/unsecure/base_link_url https://default.url/
     * @magentoConfigFixture fixturestore_store web/seo/use_rewrites 1
     * @magentoConfigFixture fixturestore_store web/unsecure/base_url https://fixturestore.url/
     * @magentoConfigFixture fixturestore_store web/unsecure/base_link_url https://fixturestore.url/
     *
     * @throws \Exception
     */
    public function testSharedVisibleChildUsesStoreSpecificParentOverridesInMultistore(): void
    {
        $specification = $this->specificationBuilder->build(['store' => 'fixturestore']);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        $sharedChildSku = 'athoscommerce_grouped_shared_child_1';
        $parentSku1 = 'athoscommerce_grouped_shared_parent_1';
        $parentSku2 = 'athoscommerce_grouped_shared_parent_2';

        $matchingRows = [];
        foreach ($data as $row) {
            $sku = (string)($row['sku'] ?? '');
            $childSku = (string)($row['child_sku'] ?? '');

            if ($sku === $sharedChildSku || $childSku === $sharedChildSku) {
                $matchingRows[] = $row;
            }
        }

        $this->assertCount(3, $matchingRows, 'Expected exactly 3 exported rows for shared visible child in fixturestore.');

        $parentRows = array_values(array_filter(
            $matchingRows,
            static function (array $row) use ($sharedChildSku): bool {
                return (string)($row['sku'] ?? '') !== $sharedChildSku
                    && (string)($row['child_sku'] ?? '') === $sharedChildSku;
            }
        ));

        $standaloneRows = array_values(array_filter(
            $matchingRows,
            static function (array $row) use ($sharedChildSku): bool {
                return (string)($row['sku'] ?? '') === $sharedChildSku;
            }
        ));

        $this->assertCount(2, $parentRows, 'Expected 2 grouped parent-context rows in fixturestore.');
        $this->assertCount(1, $standaloneRows, 'Expected 1 standalone row in fixturestore.');

        $expectedParentNames = [
            $parentSku1 => 'AthosCommerce Shared Parent 1 Fixture Store',
            $parentSku2 => 'AthosCommerce Shared Parent 2 Fixture Store',
        ];

        $expectedParentUrls = [
            $parentSku1 => 'https://fixturestore.url/fixturestore-athoscommerce-grouped-shared-parent-1.html',
            $parentSku2 => 'https://fixturestore.url/fixturestore-athoscommerce-grouped-shared-parent-2.html',
        ];

        foreach ($parentRows as $row) {
            $sku = (string)($row['sku'] ?? '');

            $this->assertArrayHasKey('name', $row);
            $this->assertSame($expectedParentNames[$sku], (string)$row['name'], 'Unexpected parent name for SKU: ' . $sku);

            if (isset($row['url'])) {
                $this->assertSame($expectedParentUrls[$sku], (string)$row['url'], 'Unexpected parent URL for SKU: ' . $sku);
            }

            $this->assertSame($sharedChildSku, (string)($row['child_sku'] ?? ''), 'Expected shared child_sku on parent row.');
        }

        $standaloneRow = $standaloneRows[0];

        $this->assertSame($sharedChildSku, (string)($standaloneRow['sku'] ?? ''));
        $this->assertSame(
            'AthosCommerce Shared Child Fixture Store',
            (string)($standaloneRow['name'] ?? ''),
            'Standalone row should use child store-specific name.'
        );

        if (isset($standaloneRow['url'])) {
            $this->assertSame(
                'https://fixturestore.url/fixturestore-athoscommerce-grouped-shared-child-1.html',
                (string)$standaloneRow['url'],
                'Standalone row should use child store-specific URL.'
            );
        }

        $this->contextManager->resetContext();
        $this->groupedDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products_shared_nvi_child.php
     *
     * @throws \Exception
     */
    public function testSharedNotVisibleChildExportsOnlyTwoParentRows(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        $sharedChildSku = 'athoscommerce_grouped_shared_nvi_child_1';
        $parentSku1 = 'athoscommerce_grouped_shared_nvi_parent_1';
        $parentSku2 = 'athoscommerce_grouped_shared_nvi_parent_2';

        $matchingRows = [];
        foreach ($data as $row) {
            $sku = (string)($row['sku'] ?? '');
            $childSku = (string)($row['child_sku'] ?? '');

            if ($sku === $sharedChildSku || $childSku === $sharedChildSku) {
                $matchingRows[] = $row;
            }
        }

        $this->assertCount(2, $matchingRows, 'Expected exactly 2 exported rows for shared NVI child.');

        $exportedSkus = array_map(static function (array $row): string {
            return (string)($row['sku'] ?? '');
        }, $matchingRows);

        $this->assertNotContains($sharedChildSku, $exportedSkus, 'Shared NVI child must not be exported as standalone.');
        $this->assertContains($parentSku1, $exportedSkus, 'Expected grouped parent export row for parent 1.');
        $this->assertContains($parentSku2, $exportedSkus, 'Expected grouped parent export row for parent 2.');

        foreach ($matchingRows as $row) {
            $this->assertSame(
                $sharedChildSku,
                (string)($row['child_sku'] ?? ''),
                'Expected parent-context rows to reference the shared NVI child.'
            );
        }

        $this->contextManager->resetContext();
        $this->groupedDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products_shared_child_disabled_parent.php
     *
     * @throws \Exception
     */
    public function testSharedVisibleChildWithDisabledParentExportsOnlyEnabledParentAndStandaloneRow(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        $sharedChildSku = 'athoscommerce_grouped_shared_disabled_parent_child_1';
        $enabledParentSku = 'athoscommerce_grouped_shared_disabled_parent_enabled';
        $disabledParentSku = 'athoscommerce_grouped_shared_disabled_parent_disabled';

        $matchingRows = [];
        foreach ($data as $row) {
            $sku = (string)($row['sku'] ?? '');
            $childSku = (string)($row['child_sku'] ?? '');

            if ($sku === $sharedChildSku || $childSku === $sharedChildSku) {
                $matchingRows[] = $row;
            }
        }

        $this->assertCount(2, $matchingRows, 'Expected 1 enabled parent row and 1 standalone row for shared child.');

        $exportedSkus = array_map(static function (array $row): string {
            return (string)($row['sku'] ?? '');
        }, $matchingRows);

        $this->assertContains($sharedChildSku, $exportedSkus, 'Expected standalone child export row.');
        $this->assertContains($enabledParentSku, $exportedSkus, 'Expected enabled grouped parent export row.');
        $this->assertNotContains($disabledParentSku, $exportedSkus, 'Disabled grouped parent must not be exported.');

        $this->contextManager->resetContext();
        $this->groupedDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products_shared_child_catalog_visibility.php
     *
     * @throws \Exception
     */
    public function testSharedCatalogVisibleChildIsExportedAsStandalone(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        $sharedChildSku = 'athoscommerce_grouped_shared_catalog_child_1';
        $parentSku1 = 'athoscommerce_grouped_shared_catalog_parent_1';
        $parentSku2 = 'athoscommerce_grouped_shared_catalog_parent_2';

        $matchingRows = [];
        foreach ($data as $row) {
            $sku = (string)($row['sku'] ?? '');
            $childSku = (string)($row['child_sku'] ?? '');

            if ($sku === $sharedChildSku || $childSku === $sharedChildSku) {
                $matchingRows[] = $row;
            }
        }

        $this->assertCount(3, $matchingRows, 'Catalog-visible shared child should export as 2 parent rows and 1 standalone row.');

        $exportedSkus = array_map(static function (array $row): string {
            return (string)($row['sku'] ?? '');
        }, $matchingRows);

        $this->assertContains($sharedChildSku, $exportedSkus);
        $this->assertContains($parentSku1, $exportedSkus);
        $this->assertContains($parentSku2, $exportedSkus);

        $this->contextManager->resetContext();
        $this->groupedDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products.php
     *
     * @throws \Exception
     */
    public function testGroupedParentRowsContainParentStockFields(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        $foundGroupedParentRow = false;

        foreach ($data as $row) {
            if (empty($row[Constant::IS_BELONG_TO_PARENT_KEY])) {
                continue;
            }

            if (($row['parent_type_id'] ?? null) !== Constant::GROUPED_TYPE) {
                continue;
            }

            $this->assertArrayHasKey('parent_in_stock', $row);
            $this->assertArrayHasKey('parent_stock_qty', $row);
            $this->assertArrayHasKey('parent_is_stock_managed', $row);
            $foundGroupedParentRow = true;
        }

        $this->assertTrue($foundGroupedParentRow, 'Expected at least one grouped parent-context row.');

        $this->contextManager->resetContext();
        $this->groupedDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products_shared_child_stock.php
     *
     * @throws \Exception
     */
    public function testSharedChildParentRowsUseParentSpecificStockValues(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        $sharedChildSku = 'athoscommerce_grouped_shared_stock_child_1';
        $parentSku1 = 'athoscommerce_grouped_shared_stock_parent_1';
        $parentSku2 = 'athoscommerce_grouped_shared_stock_parent_2';

        $matchingRows = [];
        foreach ($data as $row) {
            $sku = (string)($row['sku'] ?? '');
            $childSku = (string)($row['child_sku'] ?? '');

            if ($sku === $sharedChildSku || $childSku === $sharedChildSku) {
                $matchingRows[] = $row;
            }
        }

        $this->assertCount(3, $matchingRows, 'Expected 1 standalone row and 2 grouped parent rows.');

        $parentRows = [];
        foreach ($matchingRows as $row) {
            $sku = (string)($row['sku'] ?? '');
            if (in_array($sku, [$parentSku1, $parentSku2], true)) {
                $parentRows[$sku] = $row;
            }
        }

        $this->assertCount(2, $parentRows, 'Expected exactly 2 grouped parent rows.');

        $this->assertSame(11.0, (float)$parentRows[$parentSku1]['parent_stock_qty']);
        $this->assertSame(29.0, (float)$parentRows[$parentSku2]['parent_stock_qty']);

        $this->assertSame(1, (int)$parentRows[$parentSku1]['parent_in_stock']);
        $this->assertSame(1, (int)$parentRows[$parentSku2]['parent_in_stock']);

        $this->assertSame(
            'athoscommerce_grouped_shared_stock_child_1',
            (string)$parentRows[$parentSku1]['child_sku']
        );
        $this->assertSame(
            'athoscommerce_grouped_shared_stock_child_1',
            (string)$parentRows[$parentSku2]['child_sku']
        );

        $this->contextManager->resetContext();
        $this->groupedDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products_shared_child_stock_legacy.php
     *
     * @throws \Exception
     */
    public function testSharedChildParentRowsUseParentSpecificStockValuesLegacy(): void
    {
        $specification = $this->specificationBuilder->build([
            'isMsiEnabled' => false,
        ]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        $sharedChildSku = 'athoscommerce_grouped_shared_stock_child_legacy_1';
        $parentSku1 = 'athoscommerce_grouped_shared_stock_parent_legacy_1';
        $parentSku2 = 'athoscommerce_grouped_shared_stock_parent_legacy_2';

        $matchingRows = [];
        foreach ($data as $row) {
            $sku = (string)($row['sku'] ?? '');
            $childSku = (string)($row['child_sku'] ?? '');

            if ($sku === $sharedChildSku || $childSku === $sharedChildSku) {
                $matchingRows[] = $row;
            }
        }

        $this->assertCount(3, $matchingRows, 'Expected 1 standalone row and 2 grouped parent rows.');

        $parentRows = [];
        foreach ($matchingRows as $row) {
            $sku = (string)($row['sku'] ?? '');
            if (in_array($sku, [$parentSku1, $parentSku2], true)) {
                $parentRows[$sku] = $row;
            }
        }

        $this->assertCount(2, $parentRows, 'Expected exactly 2 grouped parent rows.');

        foreach ([$parentSku1, $parentSku2] as $parentSku) {
            $this->assertArrayHasKey($parentSku, $parentRows);
            $this->assertArrayHasKey('parent_in_stock', $parentRows[$parentSku]);
            $this->assertArrayHasKey('parent_stock_qty', $parentRows[$parentSku]);
            $this->assertArrayHasKey('parent_is_stock_managed', $parentRows[$parentSku]);
            $this->assertSame($sharedChildSku, (string)$parentRows[$parentSku]['child_sku']);
        }

        $this->assertSame(11.0, (float)$parentRows[$parentSku1]['parent_stock_qty']);
        $this->assertSame(29.0, (float)$parentRows[$parentSku2]['parent_stock_qty']);

        $this->assertSame(1, (int)$parentRows[$parentSku1]['parent_in_stock']);
        $this->assertSame(1, (int)$parentRows[$parentSku2]['parent_in_stock']);

        $this->assertSame(1, (int)$parentRows[$parentSku1]['parent_is_stock_managed']);
        $this->assertSame(1, (int)$parentRows[$parentSku2]['parent_is_stock_managed']);

        $this->assertNotSame(
            (float)$parentRows[$parentSku1]['parent_stock_qty'],
            (float)$parentRows[$parentSku2]['parent_stock_qty'],
            'Expected parent stock qty to be resolved per parent row, not overwritten by another parent.'
        );

        $this->contextManager->resetContext();
        $this->groupedDataProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products.php
     *
     * @throws \Exception
     */
    public function testReset(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $this->groupedDataProvider->getData($products, $specification);
        $this->groupedDataProvider->reset();
        $this->assertTrue(true);
    }

    /**
     * Test that __parent_id and __parent_sku are correctly set for standalone products in grouped context.
     *
     * For standalone products (visible individually, not belonging to parent):
     * - __parent_id should be based on parentIdSourceFieldName configuration (defaults to entity_id)
     * - __parent_sku should equal the product's sku
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products.php
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
                $parentId = (string)($product[Constant::PARENT_ID] ?? '');
                $parentSku = (string)($product[Constant::PARENT_SKU] ?? '');

                $this->assertNotEmpty($entityId, 'entity_id should not be empty for standalone product');
                $this->assertNotEmpty($sku, 'sku should not be empty for standalone product');

                // Parent ID should be populated (by default uses entity_id based on parentIdSourceFieldName)
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
        $this->groupedDataProvider->reset();
    }

    /**
     * Test that __parent_id and __parent_sku are correctly set for child products in grouped context.
     *
     * For child products (belonging to grouped parent):
     * - __parent_id should equal the parent's entity_id (NOT the child's entity_id)
     * - __parent_sku should equal the parent's sku (NOT the child's sku)
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products.php
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
            if (isset($product[Constant::IS_BELONG_TO_PARENT_KEY]) && $product[Constant::IS_BELONG_TO_PARENT_KEY] === true) {
                // This is a child product belonging to a grouped parent
                $entityId = (string)($product['entity_id'] ?? '');
                $sku = (string)($product['sku'] ?? '');
                $childSku = (string)($product['child_sku'] ?? '');
                $parentId = (string)($product[Constant::PARENT_ID] ?? '');
                $parentSku = (string)($product[Constant::PARENT_SKU] ?? '');
                $parentTitle = (string)($product[Constant::PARENT_TITLE] ?? '');

                $this->assertNotEmpty($entityId, 'entity_id should not be empty for child product');
                $this->assertNotEmpty($sku, 'sku should not be empty for child product');
                $this->assertNotEmpty($parentId, Constant::PARENT_ID . ' should not be empty for child product');
                $this->assertNotEmpty($parentSku, Constant::PARENT_SKU . ' should not be empty for child product');
                $this->assertNotEmpty($parentTitle, Constant::PARENT_TITLE . ' should not be empty for child product');

                // Parent ID should be different from child entity_id
                $this->assertNotEquals(
                    $entityId,
                    $parentId,
                    sprintf(
                        'Child product %s: %s should be parent\'s entity_id, not child\'s entity_id (%s)',
                        $sku,
                        Constant::PARENT_ID,
                        $entityId
                    )
                );

                // Parent SKU should be different from child sku
                $this->assertNotEquals(
                    $sku,
                    $parentSku,
                    sprintf(
                        'Child product %s: %s should be parent\'s sku, not child\'s sku',
                        $sku,
                        Constant::PARENT_SKU
                    )
                );

                // Verify that parent_id is numeric (valid entity_id)
                $this->assertTrue(
                    is_numeric($parentId) || ctype_digit((string)$parentId),
                    sprintf(
                        'Child product %s: %s should be numeric. Got %s',
                        $sku,
                        Constant::PARENT_ID,
                        $parentId
                    )
                );

                // Verify the resolved parent information is also set
                $this->assertArrayHasKey(
                    Constant::RESOLVED_PARENT_ID_KEY,
                    $product,
                    sprintf(
                        'Child product %s should have resolved parent ID key',
                        $sku
                    )
                );
                $this->assertArrayHasKey(
                    Constant::RESOLVED_PARENT_SKU_KEY,
                    $product,
                    sprintf(
                        'Child product %s should have resolved parent SKU key',
                        $sku
                    )
                );
                $this->assertArrayHasKey(
                    Constant::RESOLVED_PARENT_ROW_SOURCE_KEY,
                    $product,
                    sprintf(
                        'Child product %s should have resolved parent row source key',
                        $sku
                    )
                );
                $this->assertEquals(
                    'grouped',
                    $product[Constant::RESOLVED_PARENT_ROW_SOURCE_KEY],
                    sprintf(
                        'Child product %s: resolved parent row source should be "grouped" for grouped products',
                        $sku
                    )
                );
            }
        }

        $this->contextManager->resetContext();
        $this->groupedDataProvider->reset();
    }
}
