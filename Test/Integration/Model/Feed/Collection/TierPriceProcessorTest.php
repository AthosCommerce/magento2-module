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

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Model\Feed\Collection\TierPriceProcessor;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TierPriceProcessorTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var TierPriceProcessor
     */
    private $tierPriceProcessor;
    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->tierPriceProcessor = $this->objectManager->get(TierPriceProcessor::class);
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products_tierprice.php
     */
    public function testProcessAfterLoad() : void
    {
        $specification = $this->specificationBuilder->build(['includeTierPricing' => true]);
        $collection = $this->getCollection();
        $this->tierPriceProcessor->processAfterLoad($collection, $specification);
        $this->assertTrue($collection->getFlag('tier_price_added'));
        foreach ($collection as $item) {
            /** @var $item Product */
            if ($item->getSku() === 'athoscommerce_simple_1') {
                $this->assertNotEmpty($item->getTierPrices());
                $this->assertNotEmpty($item->getData('tier_price'));
            } else {
                $this->assertEmpty($item->getTierPrices());
                $this->assertEmpty($item->getData('tier_price'));
            }
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products_tierprice.php
     */
    public function testProcessWithAddTierPriceDataIsFalse() : void
    {
        $specification = $this->specificationBuilder->build(['includeTierPricing' => false]);
        $collection = $this->getCollection();
        $this->tierPriceProcessor->processAfterLoad($collection, $specification);
        $this->assertNull($collection->getFlag('tier_price_added'));
        foreach ($collection as $item) {
            /** @var $item Product */
            $this->assertNull($item->getData('tier_price'));
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
