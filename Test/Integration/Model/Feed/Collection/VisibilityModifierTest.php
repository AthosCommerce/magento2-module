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
use AthosCommerce\Feed\Model\Feed\Collection\VisibilityModifier;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class VisibilityModifierTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var VisibilityModifier
     */
    private $visibilityModifier;
    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->visibilityModifier = $this->objectManager->get(VisibilityModifier::class);
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_product_not_visible.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_product_visibility_catalog.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_product_visibility_search.php
     */
    public function testModify() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $collection = $this->getCollection();
        $this->visibilityModifier->modify($collection, $specification);
        $skus = [];
        foreach ($collection as $item) {
            $skus[] = $item->getSku();
        }

        $this->assertTrue(!in_array('athoscommerce_simple_not_visible', $skus));
        $this->assertTrue(in_array('athoscommerce_simple_visibility_catalog', $skus));
        $this->assertTrue(in_array('athoscommerce_simple_visibility_search', $skus));
        $this->assertTrue(in_array('athoscommerce_simple_1', $skus));
        $this->assertTrue(in_array('athoscommerce_simple_2', $skus));
    }

    /**
     * @return Collection
     */
    private function getCollection() : Collection
    {
        return $this->objectManager->create(Collection::class);
    }
}
