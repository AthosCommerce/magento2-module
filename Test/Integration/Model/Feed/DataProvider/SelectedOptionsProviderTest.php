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
use AthosCommerce\Feed\Model\Feed\DataProvider\SelectedOptionsProvider;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SelectedOptionsProviderTest extends TestCase
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
     * @var SelectedOptionsProvider
     */
    private $selectedOptionsProvider;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->getProducts = $this->objectManager->get(GetProducts::class);
        $this->selectedOptionsProvider = $this->objectManager->get(SelectedOptionsProvider::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_color_attribute_select.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_size_attribute_select.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products_for_selected_options.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable_product_for_selected_options.php
     */
    public function testSelectedOptions(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);

        $data = $this->selectedOptionsProvider->getData($products, $specification);

        $expected = [
            'simple-red-m' => [
                'athos_color' => 'Red',
                'athos_size' => 'M'
            ],
            'simple-blue-s' => [
                'athos_color' => 'Blue',
                'athos_size' => 'S'
            ]
        ];

        foreach ($data as $item) {
            $product = $item['product_model'] ?? null;

            if (!$product || $product->getTypeId() !== 'simple') {
                continue;
            }

            $sku = $product->getSku();

            if (!isset($expected[$sku])) {
                continue;
            }

            // Field must exist
            $this->assertArrayHasKey('__selected_options', $item, 'Missing __selected_options for SKU: ' . $sku);

            $json = $item['__selected_options'];

            if ($json === null) {
                $this->assertNull($json, 'Expected null for SKU: ' . $sku);
                continue;
            }

            $this->assertIsString($json, 'Expected string JSON for SKU: ' . $sku);
            $this->assertJson($json, 'Invalid JSON for SKU: ' . $sku);

            $options = json_decode($json, true);

            $this->assertIsArray($options, 'Decoded options must be array for SKU: ' . $sku);

            foreach ($expected[$sku] as $attrCode => $value) {
                $this->assertArrayHasKey($attrCode, $options, "Missing {$attrCode} for SKU: {$sku}");
                $this->assertEquals(
                    $value,
                    $options[$attrCode]['value'],
                    "Mismatch for {$attrCode} in SKU: {$sku}"
                );
            }
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_color_attribute_select.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_size_attribute_select.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products_for_selected_options.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable_product_for_selected_options.php
     */
    public function testIgnoreField(): void
    {
        $ignoredFields = ['__selected_options'];
        $specification = $this->specificationBuilder->build(['ignoreFields' => $ignoredFields]);
        $products = $this->getProducts->get($specification);

        $result = $this->selectedOptionsProvider->getData($products, $specification);
        $this->selectedOptionsProvider->reset();

        foreach ($result as $item) {
            $this->assertArrayNotHasKey('__selected_options', $item);
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_color_attribute_select.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_size_attribute_select.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products_for_selected_options.php
     */
    public function testReturnsNullWhenNoParent(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);

        $data = $this->selectedOptionsProvider->getData($products, $specification);

        foreach ($data as $item) {
            $product = $item['product_model'] ?? null;

            if (!$product || $product->getTypeId() !== 'simple') {
                continue;
            }

            $this->assertArrayHasKey('__selected_options', $item);
            $this->assertNull($item['__selected_options'], 'SKU: ' . $product->getSku());
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_color_attribute_select.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_size_attribute_select.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products_for_selected_options.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable_product_for_selected_options.php
     */
    public function testParentCacheIsReused(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);

        $data1 = $this->selectedOptionsProvider->getData($products, $specification);

        $data2 = $this->selectedOptionsProvider->getData($products, $specification);

        $this->assertEquals($data1, $data2, 'Cache should not change output');

        $this->selectedOptionsProvider->reset();
        $data3 = $this->selectedOptionsProvider->getData($products, $specification);

        $this->assertEquals($data1, $data3, 'Reset should not affect correctness');
    }
}
