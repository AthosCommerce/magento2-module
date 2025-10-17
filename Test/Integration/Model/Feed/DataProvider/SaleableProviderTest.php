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
use AthosCommerce\Feed\Model\Feed\DataProvider\SaleableProvider;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaleableProviderTest extends TestCase
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
     * @var SaleableProvider
     */
    private $saleableProvider;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->getProducts = $this->objectManager->get(GetProducts::class);
        $this->saleableProvider = $this->objectManager->get(SaleableProvider::class);
        parent::setUp();
    }

    /**
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture current_store cataloginventory/options/show_out_of_stock 1
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_product_oos.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable_products_disabled_simple.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable_products_oos_simples.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped_products.php
     *
     * @throws \Exception
     */
    public function testGetData() : void
    {
        $specification = $this->specificationBuilder->build(['includeOutOfStock' => true]);
        $products = $this->getProducts->get($specification);
        $data = $this->saleableProvider->getData($products, $specification);
        $config = [
            'athoscommerce_simple_1' => true,
            'athoscommerce_simple_2' => true,
            'athoscommerce_simple_oos' => false,
            'athoscommerce_configurable_test_configurable' => true,
            'athoscommerce_configurable_test_configurable_2_attributes' => true,
            'athoscommerce_configurable_test_oos_simple_configurable' => false,
            'athoscommerce_grouped_test_simple_1000' => true,
            'athoscommerce_grouped_test_simple_1001' => true,
            'athoscommerce_grouped_test_grouped_1' => true,
            'athoscommerce_grouped_test_simple_1010' => true,
            'athoscommerce_grouped_test_simple_1011' => true,
            'athoscommerce_grouped_test_simple_1012' => true,
            'athoscommerce_grouped_test_simple_1013' => true,
            'athoscommerce_grouped_test_grouped_2' => true
        ];
        $this->assertSaleable($data, $config);
        $this->saleableProvider->reset();
    }

    /**
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products.php
     *
     * @throws \Exception
     */
    public function testReset() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $this->saleableProvider->getData($products, $specification);
        $this->saleableProvider->reset();
        $this->assertTrue(true);
    }

    private function assertSaleable(array $items, array $config) : void
    {
        foreach ($items as $item) {
            /** @var Product $productModel */
            $productModel = $item['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            $sku = $productModel->getSku();
            $this->assertArrayHasKey('saleable', $item, 'sku: ' . $sku);
            $expectedStatus = $config[$sku] ?? null;
            if (!is_null($expectedStatus)) {
                $this->assertEquals($expectedStatus, $item['saleable'], 'sku: ' . $sku);
            }
        }
    }
}
