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
use Magento\Customer\Model\Group;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\PricesProvider;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PricesProviderTest extends TestCase
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
     * @var PricesProvider
     */
    private $pricesProvider;
    /**
     * @var ContextManagerInterface
     */
    private $contextManager;
    /**
     * @var Json
     */
    private $json;

    private $defaultPriceConfig = [
        'athoscommerce_simple_1' => [
            'final_price' => 10.0,
            'regular_price' => 10.0,
            'max_price' => 10.0
        ],
        'athoscommerce_simple_2' => [
            'final_price' => 10.0,
            'regular_price' => 10.0,
            'max_price' => 10.0
        ],
        'athoscommerce_configurable_test_configurable' => [
            'final_price' => 10.0,
            'regular_price' => 10.0,
            'max_price' => 40.0
        ],
        'athoscommerce_configurable_test_configurable_2_attributes' => [
            'final_price' => 50.0,
            'regular_price' => 50.0,
            'max_price' => 60.0
        ],
        'athoscommerce_grouped_test_grouped_1' => [
            'final_price' => 1000.0,
            'regular_price' => 0,
            'max_price' => 1000.0
        ],
        'athoscommerce_grouped_test_grouped_2' => [
            'final_price' => 1010.0,
            'regular_price' => 0,
            'max_price' => 1010.0
        ],
    ];

    private $defaultTierPriceConfig = [
        'athoscommerce_simple_1' => [
            [
                'cust_group' => Group::CUST_GROUP_ALL,
                'price_qty' => 2,
                'price' => 8
            ],
            [
                'cust_group' => Group::CUST_GROUP_ALL,
                'price_qty' => 5,
                'price' => 5
            ],
            [
                'cust_group' => Group::NOT_LOGGED_IN_ID,
                'price_qty' => 3,
                'price' => 5
            ],
        ]
    ];

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->getProducts = $this->objectManager->get(GetProducts::class);
        $this->pricesProvider = $this->objectManager->get(PricesProvider::class);
        $this->contextManager = $this->objectManager->get(ContextManagerInterface::class);
        $this->json = $this->objectManager->get(Json::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products_tierprice.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products.php
     *
     * @throws \Exception
     */
    public function testGetData() : void
    {
        $specification = $this->specificationBuilder->build(['includeTierPricing' => true]);
        $products = $this->getProducts->get($specification);
        $data = $this->pricesProvider->getData($products, $specification);
        $config = $this->buildConfig();
        $this->assertPrices($data, $config);
        $this->assertTierPrice($data, $this->buildConfig([], true));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products_specialprice.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products_specialprice.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products_specialprice.php
     *
     * @throws \Exception
     */
    public function testGetDataWithSpecialPrice() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $data = $this->pricesProvider->getData($products, $specification);
        $config = [
            'athoscommerce_simple_2' => [
                'final_price' => 6,
                'max_price' => 6
            ],
            'athoscommerce_configurable_test_configurable' => [
                'final_price' => 6,
                'max_price' => 30
            ],
            'athoscommerce_grouped_test_grouped_2' => [
                'final_price' => 1000,
                'max_price' => 1000
            ]
        ];

        $config = $this->buildConfig($config);
        $this->assertPrices($data, $config);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products_catalogrule.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products_catalogrule.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products_catalogrule.php
     *
     * @throws \Exception
     */
    public function testGetDataWithCatalogRule() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $data = $this->pricesProvider->getData($products, $specification);
        $config = [
            'athoscommerce_simple_1' => [
                'final_price' => 3,
                'max_price' => 3
            ],
            'athoscommerce_simple_2' => [
                'final_price' => 6,
                'max_price' => 6
            ],
            'athoscommerce_configurable_test_configurable' => [
                'final_price' => 8,
                'max_price' => 30
            ],
            'athoscommerce_configurable_test_configurable_2_attributes' => [
                'final_price' => 15,
                'max_price' => 30
            ],
            'athoscommerce_grouped_test_grouped_1' => [
                'final_price' => 900,
                'max_price' => 900
            ],
            'athoscommerce_grouped_test_grouped_2' => [
                'final_price' => 811,
                'max_price' => 811
            ]
        ];

        $config = $this->buildConfig($config);
        $this->assertPrices($data, $config);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products_catalogrule_with_customer_group.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products_catalogrule.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products_catalogrule.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/customer.php
     *
     * @throws \Exception
     */
    public function testGetDataWithCatalogRuleAndCustomer() : void
    {
        $specification = $this->specificationBuilder->build(['customerId' => 1]);
        $this->contextManager->setContextFromSpecification($specification);
        $products = $this->getProducts->get($specification);
        $data = $this->pricesProvider->getData($products, $specification);
        $config = [
            'athoscommerce_simple_1' => [
                'final_price' => 7,
                'max_price' => 7
            ],
            'athoscommerce_simple_2' => [
                'final_price' => 2,
                'max_price' => 2
            ]
        ];

        $config = $this->buildConfig($config);
        $this->assertPrices($data, $config);
        $this->contextManager->resetContext();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture current_store catalog/price/scope 1
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/change_price_attributes_scope.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products_multiwebsite.php
     *
     * @throws \Exception
     */
    public function testGetDataMultistore() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $data = $this->pricesProvider->getData($products, $specification);
        $config = $this->buildConfig();
        $this->assertPrices($data, $config);
        $specification = $this->specificationBuilder->build(['store' => 'fixture_second_store']);
        $this->contextManager->setContextFromSpecification($specification);
        $products = $this->getProducts->get($specification);
        $data = $this->pricesProvider->getData($products, $specification);
        $config = [
            'athoscommerce_simple_1' => [
                'final_price' => 20,
                'regular_price' => 20,
                'max_price' => 20
            ],
            'athoscommerce_simple_2' => [
                'final_price' => 20,
                'regular_price' => 20,
                'max_price' => 20
            ]
        ];

        $config = $this->buildConfig($config);
        $this->assertPrices($data, $config);
        $this->contextManager->resetContext();
    }

    /**
     * @param array $config
     * @param bool $useTierPrice
     * @return array
     */
    private function buildConfig(array $config = [], bool $useTierPrice = false) : array
    {
        $result = $useTierPrice ? $this->defaultTierPriceConfig : $this->defaultPriceConfig;
        foreach ($config as $sku => $skuConfig) {
            if (!isset($result[$sku])) {
                $result[$sku] = $skuConfig;
            }

            foreach ($skuConfig as $priceCode => $priceValue) {
                if ($priceValue === 'remove' && isset($result[$sku][$priceCode])) {
                    unset($result[$sku][$priceCode]);
                } else {
                    $result[$sku][$priceCode] = $priceValue;
                }
            }
        }

        return $result;
    }

    /**
     * @param array $items
     * @param array $config
     */
    private function assertPrices(array $items, array $config) : void
    {
        foreach ($items as $item) {
            /** @var Product $product */
            $product = $item['product_model'] ?? null;
            if (!$product) {
                continue;
            }

            $sku = $product->getSku();
            $skuConfig = $config[$sku] ?? [];
            foreach ($skuConfig as $priceCode => $priceValue) {
                $this->assertArrayHasKey($priceCode, $item, 'sku ' . $sku);
                $this->assertEquals($priceValue, $item[$priceCode], 'sku ' . $sku);
            }
        }
    }

    /**
     * @param array $items
     * @param array $config
     */
    private function assertTierPrice(array $items, array $config) : void
    {
        foreach ($items as $item) {
            /** @var Product $product */
            $product = $item['product_model'] ?? null;
            if (!$product) {
                continue;
            }

            $sku = $product->getSku();
            $this->assertArrayHasKey('tier_pricing', $item, 'sku ' . $sku);
            $skuConfig = $config[$sku] ?? [];
            $tierPrices = $this->json->unserialize($item['tier_pricing']);
            if (empty($skuConfig)) {
                $this->assertEmpty($tierPrices, 'sku ' . $sku);
            } else {
                $this->assertNotEmpty($tierPrices, 'sku ' . $sku);
                foreach ($tierPrices as $key => $value) {
                    $this->assertNotEmpty($value, 'sku ' . $sku);
                    $this->assertArrayHasKey('product_id', $value, 'sku ' . $sku);
                    $tierPriceConfig = $this->findTierPrice($value, $skuConfig);
                    $this->assertNotEmpty($tierPriceConfig, 'sku ' . $sku);
                    $this->assertArrayHasKey('__key_to_delete__', $tierPriceConfig, 'sku ' . $sku);
                    $keyToDelete = $tierPriceConfig['__key_to_delete__'];
                    unset($tierPriceConfig['__key_to_delete__']);
                    foreach ($tierPriceConfig as $tierPriceKey => $tierPriceValue) {
                        $this->assertArrayHasKey($tierPriceKey, $value, 'sku ' . $sku);
                        $this->assertEquals($tierPriceValue, $value[$tierPriceKey], 'sku ' . $sku);
                    }

                    unset($skuConfig[$keyToDelete]);
                }

                $this->assertEmpty($skuConfig, 'sku ' . $sku);
            }
        }
    }

    /**
     * @param array $tierPrice
     * @param array $tierPricesConfig
     * @return array
     */
    private function findTierPrice(array $tierPrice, array $tierPricesConfig) : array
    {
        $result = [];
        foreach ($tierPricesConfig as $tierPriceKey => $tierPriceConfig) {
            $found = true;
            foreach ($tierPriceConfig as $key => $value) {
                if (!isset($tierPrice[$key])) {
                    $found = false;
                    break;
                }

                $tierPriceValue = $tierPrice[$key] ?? null;
                if ($tierPriceValue != $value) {
                    $found = false;
                    break;
                }
            }

            if ($found) {
                $result = $tierPriceConfig;
                $result['__key_to_delete__'] = $tierPriceKey;
                break;
            }
        }

        return $result;
    }
}
