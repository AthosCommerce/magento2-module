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
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Model\Feed\Collection\AttributesModifier;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AttributesModifierTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var AttributesModifier
     */
    private $attributeModifier;
    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->attributeModifier = $this->objectManager->get(AttributesModifier::class);
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_boolean_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_decimal_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products.php
     */
    public function testModify() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $collection = $this->getCollection();
        $this->attributeModifier->modify($collection, $specification);
        $this->assertTrue($collection->isAttributeAdded('boolean_attribute'));
        $this->assertTrue($collection->isAttributeAdded('decimal_attribute'));
        foreach ($collection as $item) {
            $this->assertNotNull($item->getData('boolean_attribute'));
            $this->assertNotNull($item->getData('decimal_attribute'));
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_boolean_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_decimal_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products.php
     */
    public function testModifyWithRestrictedAttributes() : void
    {
        $specification = $this->specificationBuilder->build(['ignoreFields' => ['boolean_attribute', 'decimal_attribute']]);
        $collection = $this->getCollection();
        $this->attributeModifier->modify($collection, $specification);
        $this->assertFalse($collection->isAttributeAdded('boolean_attribute'));
        $this->assertFalse($collection->isAttributeAdded('decimal_attribute'));
        foreach ($collection as $item) {
            $this->assertNull($item->getData('boolean_attribute'));
            $this->assertNull($item->getData('decimal_attribute'));
        }
    }

    /**
     * @return Collection
     */
    private function getCollection() : Collection
    {
        return $this->objectManager->create(Collection::class);
    }
}
