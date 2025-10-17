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

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Model\Feed\Collection\StockModifier;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StockModifierTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var StockModifier
     */
    private $stockModifier;
    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->stockModifier = $this->objectManager->get(StockModifier::class);
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_product_oos.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_product_not_manage_stock.php
     */
    public function testModify() : void
    {
        $specification = $this->specificationBuilder->build(['includeOutOfStock' => true]);
        $collection = $this->getCollection();
        $this->stockModifier->modify($collection, $specification);
        $this->assertTrue($collection->getFlag('has_stock_status_filter'));
        $skus = [];
        foreach ($collection as $item) {
            $skus[] = $item->getSku();
        }

        $this->assertTrue(in_array('athoscommerce_simple_oos', $skus));
        $this->assertTrue(in_array('athoscommerce_simple_not_manage_stock', $skus));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_product_oos.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_product_not_manage_stock.php
     */
    public function testModifyWithExcludeOutOfStockProducts() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $collection = $this->getCollection();
        $this->stockModifier->modify($collection, $specification);
        $this->assertTrue($collection->getFlag('has_stock_status_filter'));
        $skus = [];
        foreach ($collection as $item) {
            $skus[] = $item->getSku();
        }

        $this->assertFalse(in_array('athoscommerce_simple_oos', $skus));
        $this->assertTrue(in_array('athoscommerce_simple_not_manage_stock', $skus));
    }

    /**
     * @return Collection
     */
    private function getCollection() : Collection
    {
        return $this->objectManager->create(Collection::class);
    }
}
