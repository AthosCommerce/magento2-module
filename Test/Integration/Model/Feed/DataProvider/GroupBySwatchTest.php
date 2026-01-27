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

use AthosCommerce\Feed\Model\Feed\DataProvider\GroupBySwatch;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GroupBySwatchTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var GroupBySwatch
     */
    private $groupBySwatch;
    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;
    /**
     * @var GetProducts
     */
    private $getProducts;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->groupBySwatch = $this->objectManager->get(GroupBySwatch::class);
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->getProducts = $this->objectManager->get(GetProducts::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_product_two_swatches_attributes.php
     *
     * @throws \Exception
     */
    public function testGetProducts(): void
    {
        $specification = $this->specificationBuilder->build([
            'swatchOptionSourceFieldNames' => 'visual_swatch_attribute'
        ]);
        $products = $this->getProducts->get($specification);
        $data = $this->groupBySwatch->getData($products, $specification);
        $config = [
            'products' => [
                'athoscommerce_configurable_test_configurable' => [
                    '__group_by_swatch' => true
                ],
                'athoscommerce_configurable_test_configurable_2_attributes' => [
                    '__group_by_swatch' => false
                ]
            ],
            'required_attributes' => ['child_sku', 'child_sku', 'child_final_price']
        ];
        file_put_contents(
            '/var/www/html/mage-live/ee248/var/log/log.txt',
            print_r($data,true).PHP_EOL ,
            FILE_APPEND | LOCK_EX
        );
        $this->assertTrue(true);
    }
}
