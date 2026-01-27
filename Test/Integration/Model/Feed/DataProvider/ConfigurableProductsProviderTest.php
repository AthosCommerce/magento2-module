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

use Magento\Catalog\Model\Product;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\ConfigurableProductsProvider;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigurableProductsProviderTest extends TestCase
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
     * @var ConfigurableProductsProvider
     */
    private $configurableProductsProvider;
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
        $this->configurableProductsProvider = $this->objectManager->get(ConfigurableProductsProvider::class);
        $this->contextManager = $this->objectManager->get(ContextManagerInterface::class);
        $this->assertChildProducts = $this->objectManager->get(AssertChildProducts::class);
        $this->markTestSkipped('All tests in this class are temporarily disabled.');
        parent::setUp();
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
        $products = $this->getProducts->get($specification);
        $data = $this->configurableProductsProvider->getData($products, $specification);
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

        $this->assertChildProducts->assertChildProducts($data, $config);
        $this->configurableProductsProvider->reset();
    }

    

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products_not_visible_individually.php
     *
     * @throws \Exception
     */
    public function testGetDataWithNotVisible(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $data = $this->configurableProductsProvider->getData(
            $products,
            $specification
        );
        $this->assertEmpty(
            $data,
            'Expected no products when parent and its childs are set to not visible.' . var_dump($data)
        );

        $this->configurableProductsProvider->reset();
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
        $this->configurableProductsProvider->getData($products, $specification);
        $this->configurableProductsProvider->reset();
        $this->assertTrue(true);
    }
}
