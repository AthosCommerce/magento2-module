<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Integration\Model\Feed\DataProvider;

use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentRelationsContext;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\VariantPosition;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use Magento\Catalog\Model\Product;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class VariantPositionTest extends TestCase
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
     * @var VariantPosition
     */
    private $variantPosition;

    /**
     * @var ContextManagerInterface
     */
    private $contextManager;

    /**
     * @var ParentRelationsContext
     */
    private $parentRelationsContext;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->getProducts = $this->objectManager->get(GetProducts::class);
        $this->variantPosition = $this->objectManager->get(VariantPosition::class);
        $this->contextManager = $this->objectManager->get(ContextManagerInterface::class);
        $this->parentRelationsContext = $this->objectManager->get(ParentRelationsContext::class);

        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/entity_id_provider_configurable_products.php
     *
     * @throws \Exception
     */
    public function testGetDataForConfigurableProducts(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);

        $childIds = [];
        foreach ($products as $row) {
            $productModel = $row['product_model'] ?? null;
            if ($productModel instanceof Product && in_array($productModel->getTypeId(), ['simple', 'virtual'], true)) {
                $childIds[] = (int)$productModel->getId();
            }
        }

        $this->parentRelationsContext->buildContext($childIds, $specification);

        $result = $this->variantPosition->getData($products, $specification);

        $positions = [];
        foreach ($result as $row) {
            $productModel = $row['product_model'] ?? null;
            if (!$productModel instanceof Product) {
                continue;
            }

            if (isset($row['__variant_position']) && $row['__variant_position'] !== null) {
                $positions[(string)$productModel->getSku()] = $row['__variant_position'];
            }
        }

        $this->assertNotEmpty($positions);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products.php
     *
     * @throws \Exception
     */
    public function testGetDataForGroupedProducts(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);

        $childIds = [];
        foreach ($products as $row) {
            $productModel = $row['product_model'] ?? null;
            if ($productModel instanceof Product && in_array($productModel->getTypeId(), ['simple', 'virtual'], true)) {
                $childIds[] = (int)$productModel->getId();
            }
        }

        $this->parentRelationsContext->buildContext($childIds, $specification);

        $result = $this->variantPosition->getData($products, $specification);

        $expectedPositions = [
            'athoscommerce_grouped_test_simple_1000' => 1,
            'athoscommerce_grouped_test_simple_1001' => 2,
            'athoscommerce_grouped_test_simple_1010' => 1,
            'athoscommerce_grouped_test_simple_1011' => 2,
            'athoscommerce_grouped_test_simple_1012' => 3,
            'athoscommerce_grouped_test_simple_1013' => 4,
        ];

        $actualPositions = [];

        foreach ($result as $row) {
            $productModel = $row['product_model'] ?? null;
            if (!$productModel instanceof Product) {
                continue;
            }

            $sku = (string)$productModel->getSku();

            if (!array_key_exists($sku, $expectedPositions)) {
                continue;
            }

            if (!array_key_exists('__variant_position', $row)) {
                continue;
            }

            if ($row['__variant_position'] === null || $row['__variant_position'] === '{}') {
                continue;
            }

            if (!isset($actualPositions[$sku])) {
                $actualPositions[$sku] = (int)$row['__variant_position'];
            }
        }

        $this->assertSame($expectedPositions, $actualPositions);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/entity_id_provider_configurable_products.php
     *
     * @throws \Exception
     */
    public function testGetDataSkipsWhenVariantPositionIsIgnored(): void
    {
        $specification = $this->specificationBuilder->build([
            'ignoreFields' => ['__variant_position'],
        ]);

        $products = $this->getProducts->get($specification);

        $result = $this->variantPosition->getData($products, $specification);

        foreach ($result as $row) {
            $this->assertArrayNotHasKey('__variant_position', $row);
        }
    }
}
