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

use AthosCommerce\Feed\Model\Feed\Collection\ExcludeProductIdsModifier;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExcludeProductIdsModifierTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var ExcludeProductIdsModifier
     */
    private $excludeProductIdsModifier;
    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->excludeProductIdsModifier = $this->objectManager->get(ExcludeProductIdsModifier::class);
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
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
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_excluded_products.php
     */
    public function testModify(): void
    {
        $excludedProductIds = [
            $this->productRepository->get('athoscommerce_simple_exclude_1')->getId(),
            $this->productRepository->get('athoscommerce_simple_exclude_2')->getId()
        ];
        $specification = $this->specificationBuilder->build(
            [
                'excludedProductIds' => $excludedProductIds
            ]
        );
        $collection = $this->getCollection();
        $this->excludeProductIdsModifier->modify($collection, $specification);
        $skus = [];
        foreach ($collection as $item) {
            $skus[] = $item->getSku();
        }

        $this->assertTrue(!in_array('athoscommerce_simple_exclude_1', $skus));
        $this->assertTrue(!in_array('athoscommerce_simple_exclude_2', $skus));
    }

    /**
     * @return Collection
     */
    private function getCollection(): Collection
    {
        return $this->objectManager->create(Collection::class);
    }
}
