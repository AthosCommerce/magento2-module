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
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\OptionsProvider;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OptionsProviderTest extends TestCase
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
     * @var OptionsProvider
     */
    private $optionsProvider;
    /**
     * @var ContextManagerInterface
     */
    private $contextManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->getProducts = $this->objectManager->get(GetProducts::class);
        $this->optionsProvider = $this->objectManager->get(OptionsProvider::class);
        $this->contextManager = $this->objectManager->get(ContextManagerInterface::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products_with_options.php
     *
     * @throws \Exception
     */
    public function testGetData() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $data = $this->optionsProvider->getData($products, $specification);
        $map = [
            'athoscommerce_simple_1' => [
                'option_drop_down_option_1' => [
                    'drop_down option 1 1', 'drop_down option 2 1'
                ]
            ],
            'athoscommerce_simple_2' => [
                'option_drop_down_option_2' => [
                    'drop_down option 1 2', 'drop_down option 2 2'
                ]
            ]
        ];

        $restrictedOptions = [
            'athoscommerce_simple_1' => [
                'test_option_code_1', 'area_option', 'multiple_option', 'date_option'
            ],
        ];

        $this->assertOptions($data, $map, $restrictedOptions);
        $this->optionsProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products_with_options_multistore.php
     *
     * @throws \Exception
     */
    public function testGetDataMultistore() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);
        $products = $this->getProducts->get($specification);
        $data = $this->optionsProvider->getData($products, $specification);
        $map = [
            'athoscommerce_simple_1' => [
                'option_store_default_drop_down_option_1' => [
                    'Store Default drop_down option 1 1', 'Store Default drop_down option 2 1'
                ]
            ],
            'athoscommerce_simple_2' => [
                'option_store_default_drop_down_option_2' => [
                    'Store Default drop_down option 1 2', 'Store Default drop_down option 2 2'
                ]
            ]
        ];

        $restrictedOptions = [
            'athoscommerce_simple_1' => [
                'test_option_code_1', 'area_option', 'multiple_option', 'date_option'
            ],
        ];

        $this->assertOptions($data, $map, $restrictedOptions);
        $this->optionsProvider->reset();
        $this->contextManager->resetContext();
    }

    /**
     * @param array $products
     * @param array $map
     * @param array $restrictedOptions
     */
    private function assertOptions(array $products, array $map, array $restrictedOptions = []) : void
    {
        foreach ($products as $product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            $sku = $productModel->getSku();
            $optionsMap = $map[$sku];
            foreach ($optionsMap as $optionKey => $optionValues) {
                $this->assertArrayHasKey($optionKey, $product);
                $this->assertEmpty(array_diff($product[$optionKey], $optionValues));
                $this->assertEmpty(array_diff($optionValues, $product[$optionKey]));
            }

            $restrictedOptionsBySku = $restrictedOptions[$sku] ?? [];
            foreach ($restrictedOptionsBySku as $restrictedOption) {
                $this->assertArrayNotHasKey($restrictedOption, $product);
            }
        }
    }
}
