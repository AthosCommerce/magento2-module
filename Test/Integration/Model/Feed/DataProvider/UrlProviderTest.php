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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\UrlProvider;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UrlProviderTest extends TestCase
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
     * @var UrlProvider
     */
    private $urlProvider;
    /**
     * @var ContextManagerInterface
     */
    private $contextManager;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->getProducts = $this->objectManager->get(GetProducts::class);
        $this->urlProvider = $this->objectManager->get(UrlProvider::class);
        $this->contextManager = $this->objectManager->get(ContextManagerInterface::class);
        $this->scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        parent::setUp();
    }

    /**
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture current_store web/unsecure/base_url https://default.url/
     * @magentoConfigFixture current_store web/unsecure/base_link_url https://default.url/
     * @magentoConfigFixture current_store web/seo/use_rewrites 1
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products_with_url.php
     *
     * @throws \Exception
     */
    public function testGetData() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $data = $this->urlProvider->getData($products, $specification);
        $config = [
            'athoscommerce_simple_1' => 'https://default.url/athoscommerce-simple-1.html',
            'athoscommerce_simple_2' => 'https://default.url/athoscommerce-simple-2.html'
        ];
        $this->assertUrl($data, $config);
        $this->urlProvider->reset();
    }

    /**
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture current_store web/unsecure/base_url https://default.url/
     * @magentoConfigFixture current_store web/unsecure/base_link_url https://default.url/
     * @magentoConfigFixture current_store web/seo/use_rewrites 1
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/remove_url_rewrite_suffix.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products_with_url.php
     *
     * @throws \Exception
     */
    public function testGetDataWithoutHtmlSuffix() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $data = $this->urlProvider->getData($products, $specification);
        $config = [
            'athoscommerce_simple_1' => 'https://default.url/athoscommerce-simple-1',
            'athoscommerce_simple_2' => 'https://default.url/athoscommerce-simple-2'
        ];
        $this->assertUrl($data, $config);
        $this->urlProvider->reset();
    }

    /**
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture current_store web/unsecure/base_url https://default.url/
     * @magentoConfigFixture current_store web/unsecure/base_link_url https://default.url/
     * @magentoConfigFixture current_store web/seo/use_rewrites 1
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products_multistore_with_url.php
     * @magentoConfigFixture fixturestore_store web/seo/use_rewrites 1
     * @magentoConfigFixture fixturestore_store web/unsecure/base_url https://fixturestore.url/
     * @magentoConfigFixture fixturestore_store web/unsecure/base_link_url https://fixturestore.url/
     *
     * @throws \Exception
     */
    public function testGetDataMultistore() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $data = $this->urlProvider->getData($products, $specification);
        $config = [
            'athoscommerce_simple_1' => 'https://default.url/simple-1.html',
            'athoscommerce_simple_2' => 'https://default.url/simple-2.html'
        ];
        $this->assertUrl($data, $config);
        $specification = $this->specificationBuilder->build(['store' => 'fixturestore']);
        $this->contextManager->setContextFromSpecification($specification);
        $products = $this->getProducts->get($specification);
        $data = $this->urlProvider->getData($products, $specification);
        $config = [
            'athoscommerce_simple_1' => 'https://fixturestore.url/fixturestore-athoscommerce-simple-1.html',
            'athoscommerce_simple_2' => 'https://fixturestore.url/fixturestore-athoscommerce-simple-2.html'
        ];
        $this->assertUrl($data, $config);
        $this->contextManager->resetContext();
    }

    /**
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products_with_url.php
     *
     * @throws \Exception
     */
    public function testReset() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $this->urlProvider->getData($products, $specification);
        $this->urlProvider->reset();
        $this->assertTrue(true);
    }

    /**
     * @param array $items
     * @param array $config
     */
    private function assertUrl(array $items, array $config) : void
    {
        foreach ($items as $item) {
            /** @var Product $productModel */
            $productModel = $item['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            $sku = $productModel->getSku();
            $this->assertArrayHasKey('url', $item);
            $expectedUrl = $config[$sku] ?? null;
            if ($expectedUrl) {
                $this->assertEquals($expectedUrl, $item['url']);
            }
        }
    }
}
