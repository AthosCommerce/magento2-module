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

namespace AthosCommerce\Feed\Test\Integration\Model\Feed\Collection;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Model\Feed\Collection\StoreModifier;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StoreModifierTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var StoreModifier
     */
    private $storeModifier;
    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeModifier = $this->objectManager->get(StoreModifier::class);
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products_multistore.php
     */
    public function testModify() : void
    {
        $storeCode = 'fixturestore';
        $specification = $this->specificationBuilder->build(['store' => $storeCode]);
        $collection = $this->getCollection();
        $this->storeModifier->modify($collection, $specification);
        // add name attribute to filter to check if we load a specific store data in collection
        $collection->addAttributeToSelect('name');
        $store = $this->objectManager->create(Store::class);
        $store->load($storeCode, 'code');
        $storeId = $store->getId();
        $this->assertNotNull($storeId);
        $this->assertEquals((int) $storeId, $collection->getStoreId());
        $item = $collection->getFirstItem();
        $sku = $item->getSku();
        $this->assertEquals('StoreTitle', $item->getName());
        $product = $this->productRepository->get($sku);
        // check that for default store same product have a different name
        $this->assertNotEquals('StoreTitle', $product->getName());
    }

    /**
     * @return Collection
     */
    private function getCollection() : Collection
    {
        return $this->objectManager->create(Collection::class);
    }
}
