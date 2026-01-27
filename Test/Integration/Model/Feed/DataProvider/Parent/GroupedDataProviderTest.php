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

use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\GroupedDataProvider;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
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
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->getProducts = $this->objectManager->get(GetProducts::class);
        $this->groupedDataProvider = $this->objectManager->get(GroupedDataProvider::class);
        $this->contextManager = $this->objectManager->get(ContextManagerInterface::class);
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
        $products = $this->getProducts->get($specification);
        $this->assertNotEmpty($products);
        $data = $this->groupedDataProvider->getData(
            $products,
            $specification
        );
        $this->assertEmpty($data);

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
    public function testGetData(): void
    {
        $specification = $this->specificationBuilder->build(['includeChildPrices' => true]);
        $products = $this->getProducts->get($specification);
        $data = $this->groupedDataProvider->getData($products, $specification);
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
            'required_attributes' => ['child_sku', 'child_sku', 'child_final_price']
        ];

        $this->assertChildProducts($data, $config);
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
        $products = $this->getProducts->get($specification);
        $data = $this->groupedDataProvider->getData($products, $specification);
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
        $specification = $this->specificationBuilder->build(['includeChildPrices' => true,]);
        $products = $this->getProducts->get($specification);
        $data = $this->groupedDataProvider->getData($products, $specification);
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
        $products = $this->getProducts->get($specification);
        $data = $this->groupedDataProvider->getData($products, $specification);
        foreach ($data as $product) {
            $this->assertArrayNotHasKey('child_sku', $product);
            $this->assertArrayNotHasKey('child_name', $product);
        }
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
     * @param array $products
     * @param array $config
     */
    private function assertChildProducts(array $products, array $config): void
    {
        $productsConfig = $config['products'] ?? [];
        $requiredAttributes = $config['required_attributes'] ?? [];
        $additionalAttributes = $config['additional_attributes'] ?? [];
        $restrictedAttributes = $config['restricted_attributes'] ?? [];
        foreach ($products as $product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            $sku = $productModel->getSku();
            // its simple product
            if (!empty($productsConfig) && !isset($productsConfig[$sku])) {
                // check that simple product doesnt have any configurable product related keys
                $this->assertAttributesNotExist($product, $requiredAttributes);
            } else {
                $this->assertAttributesExist($product, $requiredAttributes);
                $this->assertAttributesExist($product, $additionalAttributes);
                $this->assertAttributesNotExist($product, $restrictedAttributes);

                $childCount = $productsConfig[$sku]['child_count'] ?? null;
                $skuPrefix = $productsConfig[$sku]['sku_prefix'] ?? null;
                $namePrefix = $productsConfig[$sku]['name_prefix'] ?? null;
                $valueMap = $productsConfig[$sku]['value_map'] ?? null;
                if (!is_null($childCount)) {
                    $this->assertCount((int)$childCount, $product['child_sku'] ?? []);
                }

                if (!is_null($skuPrefix)) {
                    $skus = $product['child_sku'] ?? [];
                    foreach ($skus as $childSku) {
                        $this->assertTrue(strpos($childSku, $skuPrefix) === 0);
                    }
                }

                if (!is_null($namePrefix)) {
                    $names = $product['child_name'] ?? [];
                    foreach ($names as $name) {
                        $this->assertTrue(strpos($name, $namePrefix) === 0);
                    }
                }

                if (!is_null($valueMap)) {
                    $this->assertValueMap($product, $valueMap);
                }
            }
        }
    }

    /**
     * @param array $data
     * @param array $valueMap
     */
    private function assertValueMap(array $data, array $valueMap): void
    {
        foreach ($valueMap as $field => $value) {
            $fieldValues = $data[$field] ?? [];
            foreach ($fieldValues as $fieldValue) {
                $this->assertTrue(in_array($fieldValue, $value));
                $key = array_search($fieldValue, $value);
                unset($value[$key]);
            }

            $this->assertEmpty($value);
        }
    }

    /**
     * @param array $data
     * @param array $attributes
     */
    private function assertAttributesExist(array $data, array $attributes): void
    {
        foreach ($attributes as $attribute) {
            $this->assertArrayHasKey($attribute, $data);
        }
    }

    /**
     * @param array $data
     * @param array $attributes
     */
    private function assertAttributesNotExist(array $data, array $attributes): void
    {
        foreach ($attributes as $attribute) {
            $this->assertArrayNotHasKey($attribute, $data);
        }
    }
}
