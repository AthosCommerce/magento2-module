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

namespace AthosCommerce\Feed\Test\Integration\Model\Feed\DataProvider\Stock;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\StockProviderInterface;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use AthosCommerce\Feed\Test\Integration\Model\Feed\DataProvider\GetProducts;

abstract class StockProviderTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * @var GetProducts
     */
    protected $getProducts;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var SpecificationBuilderInterface
     */
    protected $specificationBuilder;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->getProducts = $this->objectManager->get(GetProducts::class);
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        parent::setUp();
    }

    /**
     * @param StockProviderInterface $provider
     * @param array $config
     * @param array $specificationConfig
     * @param int|null $storeId
     * @throws NoSuchEntityException
     */
    protected function executeTest(
        StockProviderInterface $provider,
        array $config,
        array $specificationConfig = [],
        int $storeId = null
    ) : void {
        $specification = $this->specificationBuilder->build($specificationConfig);
        $products = $this->getProducts->get($specification);
        $storeId = $storeId ?? (int) $this->storeManager->getStore()->getId();
        $data = $provider->getStock($this->getProductIds($products), $storeId);
        $this->assertStock($data, $this->getProductIdSkuMap($products), $config);
    }

    /**
     * @param array $products
     * @return array
     */
    protected function getProductIdSkuMap(array $products) : array
    {
        $result = [];
        foreach ($products as $product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            $result[(int) $productModel->getId()] = $productModel->getSku();
        }

        return $result;
    }

    /**
     * @param array $products
     * @return array
     */
    protected function getProductIds(array $products) : array
    {
        $productIds = [];
        foreach ($products as $product) {
            if (isset($product['entity_id'])) {
                $productIds[] = (int) $product['entity_id'];
            }
        }

        return array_unique($productIds);
    }

    /**
     * @param array $items
     * @param array $productIdSkuMap
     * @param array $config
     */
    protected function assertStock(array $items, array $productIdSkuMap, array $config) : void
    {
        foreach ($items as $productId => $item) {
            $sku = $productIdSkuMap[$productId] ?? null;
            if (!$sku) {
                continue;
            }

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
