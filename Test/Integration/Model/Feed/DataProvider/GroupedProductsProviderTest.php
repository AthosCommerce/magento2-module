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

namespace AthosCommerce\Feed\Test\Integration\Model\Feed\DataProvider;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\GroupedProductsProvider;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GroupedProductsProviderTest extends TestCase
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
     * @var GroupedProductsProvider
     */
    private $groupedProductsProvider;
    /**
     * @var ContextManagerInterface
     */
    private $contextManager;
    /**
     * @var AssertChildProducts
     */
    private $assertChildProducts;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->getProducts = $this->objectManager->get(GetProducts::class);
        $this->groupedProductsProvider = $this->objectManager->get(GroupedProductsProvider::class);
        $this->contextManager = $this->objectManager->get(ContextManagerInterface::class);
        $this->assertChildProducts = $this->objectManager->get(AssertChildProducts::class);
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
    public function testGetData() : void
    {
        $specification = $this->specificationBuilder->build(['includeChildPrices' => true]);
        $products = $this->getProducts->get($specification);
        $data = $this->groupedProductsProvider->getData($products, $specification);
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

        $this->assertChildProducts->assertChildProducts($data, $config);
        $this->groupedProductsProvider->reset();
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
    public function testGetDataWithAdditionalAttributes() : void
    {
        $specification = $this->specificationBuilder->build([
            'includeChildPrices' => true,
            'childFields' => ['boolean_attribute', 'decimal_attribute']
        ]);
        $products = $this->getProducts->get($specification);
        $data = $this->groupedProductsProvider->getData($products, $specification);
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

        $this->assertChildProducts->assertChildProducts($data, $config);
        $this->groupedProductsProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products_with_store_value.php
     *
     * @throws \Exception
     */
    public function testGetDataWithMultistoreValues() : void
    {
        $specification = $this->specificationBuilder->build(['includeChildPrices' => true,]);
        $products = $this->getProducts->get($specification);
        $data = $this->groupedProductsProvider->getData($products, $specification);
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

        $this->assertChildProducts->assertChildProducts($data, $config);
        $this->groupedProductsProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products_disabled_simple.php
     *
     * @throws \Exception
     */
    public function testGetDataWithDisabledSimples() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $data = $this->groupedProductsProvider->getData($products, $specification);
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
    public function testReset() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $this->groupedProductsProvider->getData($products, $specification);
        $this->groupedProductsProvider->reset();
        $this->assertTrue(true);
    }
}
