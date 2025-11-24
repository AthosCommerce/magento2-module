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
use AthosCommerce\Feed\Model\Feed\Collection\ProductTypeIdModifier;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductTypeIdModifierTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var ProductTypeIdModifier
     */
    private $productTypeIdModifier;
    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productTypeIdModifier = $this->objectManager->get(ProductTypeIdModifier::class);
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        parent::setUp();
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_product_not_visible.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_product_visibility_catalog.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_product_visibility_search.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products.php
     */
    public function testModify(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $collection = $this->getCollection();
        $this->productTypeIdModifier->modify($collection, $specification);
        $skus = [];
        foreach ($collection as $item) {
            $skus[] = $item->getSku();
        }

        $this->assertTrue(!in_array('athoscommerce_grouped_test_grouped', $skus));
        $this->assertTrue(in_array('athoscommerce_grouped_test_simple_1000', $skus));
        $this->assertTrue(in_array('athoscommerce_grouped_test_simple_1001', $skus));

        $this->assertTrue(!in_array('athoscommerce_configurable_test_configurable', $skus));
        $this->assertTrue(in_array('athoscommerce_configurable_test_simple_10', $skus));
        $this->assertTrue(in_array('athoscommerce_configurable_test_simple_20', $skus));

        $this->assertTrue(in_array('athoscommerce_simple_visibility_search', $skus));
        $this->assertTrue(in_array('athoscommerce_simple_1', $skus));
        $this->assertTrue(in_array('athoscommerce_simple_2', $skus));
    }

    /**
     * @return Collection
     */
    private function getCollection(): Collection
    {
        return $this->objectManager->create(Collection::class);
    }
}
