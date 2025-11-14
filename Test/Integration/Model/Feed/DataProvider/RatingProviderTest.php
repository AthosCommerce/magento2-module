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
use AthosCommerce\Feed\Model\Feed\DataProvider\RatingProvider;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RatingProviderTest extends TestCase
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
     * @var RatingProvider
     */
    private $ratingProvider;
    /**
     * @var ContextManagerInterface
     */
    private $contextManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->getProducts = $this->objectManager->get(GetProducts::class);
        $this->ratingProvider = $this->objectManager->get(RatingProvider::class);
        $this->contextManager = $this->objectManager->get(ContextManagerInterface::class);
        parent::setUp();
    }

    /**
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products_with_rating.php
     *
     * @throws \Exception
     */
    public function testGetData() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $data = $this->ratingProvider->getData($products, $specification);
        $config = [
            'athoscommerce_simple_1' => [
                'rating' => 1.5,
                'rating_count' => 2
            ],
            'athoscommerce_simple_2' => [
                'rating' => 4,
                'rating_count' => 3
            ]
        ];
        $this->assertRating($data, $config);
        $this->ratingProvider->reset();
    }

    /**
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products_with_rating_multistore.php
     *
     * @throws \Exception
     */
    public function testGetDataMultistore() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $data = $this->ratingProvider->getData($products, $specification);
        $config = [
            'athoscommerce_simple_1' => [
                'rating' => 1.5,
                'rating_count' => 2
            ],
            'athoscommerce_simple_2' => [
                'rating' => 4,
                'rating_count' => 3
            ]
        ];
        $this->assertRating($data, $config);
        $specification = $this->specificationBuilder->build(['store' => 'fixturestore']);
        $this->contextManager->setContextFromSpecification($specification);
        $products = $this->getProducts->get($specification);
        $data = $this->ratingProvider->getData($products, $specification);
        $config = [
            'athoscommerce_simple_1' => [
                'rating' => 4,
                'rating_count' => 3
            ],
            'athoscommerce_simple_2' => [
                'rating' => 1.5,
                'rating_count' => 2
            ]
        ];
        $this->assertRating($data, $config);
        $this->contextManager->resetContext();
        $this->ratingProvider->reset();
    }

    /**
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products_with_rating.php
     *
     * @throws \Exception
     */
    public function testReset() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $this->ratingProvider->getData($products, $specification);
        $this->ratingProvider->reset();
        $this->assertTrue(true);
    }

    /**
     * @param array $items
     * @param array $config
     */
    private function assertRating(array $items, array $config) : void
    {
        foreach ($items as $item) {
            /** @var Product $productModel */
            $productModel = $item['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            $sku = $productModel->getSku();
            $ratingConfig = $config[$sku] ?? [];
            if (empty($ratingConfig)) {
                continue;
            } else {
                foreach ($ratingConfig as $key => $value) {
                    if (is_null($ratingConfig[$key])) {
                        $this->assertArrayNotHasKey($key, $item, 'sku: ' . $sku);
                    } else {
                        $this->assertArrayHasKey($key, $item, 'sku: ' . $sku);
                        $this->assertEquals($value, $item[$key], 'sku: ' . $sku . '; key: ' . $key);
                    }
                }
            }
        }
    }
}
