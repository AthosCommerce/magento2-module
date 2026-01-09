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
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Model\Feed\DataProvider\StockProvider;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StockProviderTest extends TestCase
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
     * @var StockProvider
     */
    private $stockProvider;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->getProducts = $this->objectManager->get(GetProducts::class);
        $this->stockProvider = $this->objectManager->get(StockProvider::class);
        parent::setUp();
    }

    /**
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture current_store cataloginventory/options/show_out_of_stock 1
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_product_oos.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products_oos_simples.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products.php
     *
     * @throws \Exception
     */
    public function testGetData(): void
    {
        $specification = $this->specificationBuilder->build(['includeOutOfStock' => true]);
        $products = $this->getProducts->get($specification);
        $data = $this->stockProvider->getData($products, $specification);
        $config = [
            'athoscommerce_simple_1' => ['in_stock' => 1, 'stock_qty' => 100],
            'athoscommerce_simple_2' => ['in_stock' => 1, 'stock_qty' => 100],
            'athoscommerce_simple_oos' => ['in_stock' => 0, 'stock_qty' => 100],
            'athoscommerce_configurable_test_configurable' => ['in_stock' => 1, 'stock_qty' => 0],
            'athoscommerce_configurable_test_configurable_2_attributes' => ['in_stock' => 1, 'stock_qty' => 0],
            'athoscommerce_configurable_test_oos_simple_configurable' => ['in_stock' => 0, 'stock_qty' => 0],
            'athoscommerce_grouped_test_simple_1000' => ['in_stock' => 1, 'stock_qty' => 100],
            'athoscommerce_grouped_test_simple_1001' => ['in_stock' => 1, 'stock_qty' => 100],
            'athoscommerce_grouped_test_simple_1010' => ['in_stock' => 1, 'stock_qty' => 100],
            'athoscommerce_grouped_test_simple_1011' => ['in_stock' => 1, 'stock_qty' => 100],
            'athoscommerce_grouped_test_simple_1012' => ['in_stock' => 1, 'stock_qty' => 100],
            'athoscommerce_grouped_test_simple_1013' => ['in_stock' => 1, 'stock_qty' => 100],
            'athoscommerce_grouped_test_grouped_1' => ['in_stock' => 1, 'stock_qty' => 0],
            'athoscommerce_grouped_test_grouped_2' => ['in_stock' => 1, 'stock_qty' => 0],
        ];
        $this->assertStock($data, $config);
        $this->stockProvider->reset();
    }

    /**
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     *
     * @throws \Exception
     */
    public function testReset(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $this->stockProvider->getData($products, $specification);
        $this->stockProvider->reset();
        $this->assertTrue(true);
    }

    /**
     * @param array $items
     * @param array $config
     */
    private function assertStock(array $items, array $config): void
    {
        foreach ($items as $item) {
            /** @var Product $product */
            $product = $item['product_model'] ?? null;
            if (!$product) {
                continue;
            }

            $sku = $product->getSku();
            $productConfig = $config[$sku] ?? [];
            foreach ($productConfig as $key => $value) {
                if (!is_null($value)) {
                    $this->assertArrayHasKey($key, $item, 'sku: ' . $sku . ';key: ' . $key);
                    $this->assertEquals($value, $item[$key], 'sku: ' . $sku . ';key: ' . $key);
                } else {
                    $this->assertArrayNotHasKey($key, $item, 'sku: ' . $sku . ';key: ' . $key);
                }
            }
        }
    }
}
