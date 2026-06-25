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

use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentRelationsContext;
use AthosCommerce\Feed\Model\Feed\DataProvider\GroupIdProvider;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use Magento\Catalog\Model\Product;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GroupIdProviderTest extends TestCase
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
     * @var GroupIdProvider
     */
    private $groupIdProvider;

    /**
     * @var ParentRelationsContext
     */
    private $parentRelationsContext;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->getProducts = $this->objectManager->get(GetProducts::class);
        $this->groupIdProvider = $this->objectManager->get(GroupIdProvider::class);
        $this->parentRelationsContext = $this->objectManager->get(ParentRelationsContext::class);

        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_color_attribute_select.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_size_attribute_select.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products_for_selected_options.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable_product_for_selected_options.php
     */
    public function testGetDataForConfigurableProductsUsingColor(): void
    {
        $specification = $this->specificationBuilder->build([
            'groupBySourceFieldName' => 'athos_color'
        ]);

        $products = $this->getProducts->get($specification);

        $childIds = [];
        foreach ($products as $product) {
            /** @var Product|null $productModel */
            $productModel = $product['product_model'] ?? null;

            if ($productModel && in_array($productModel->getTypeId(), ['simple', 'virtual'], true)) {
                $childIds[] = (int)$productModel->getId();
            }
        }

        $this->parentRelationsContext->buildContext($childIds, $specification);

        $data = $this->groupIdProvider->getData($products, $specification);

        $this->assertNotEmpty($data);

        $expectedBySku = [
            'simple-red-m' => 'Red',
            'simple-blue-s' => 'Blue',
        ];

        $asserted = 0;

        foreach ($data as $row) {
            /** @var Product|null $productModel */
            $productModel = $row['product_model'] ?? null;

            if (!$productModel || $productModel->getTypeId() !== 'simple') {
                continue;
            }

            $sku = $productModel->getSku();

            if (!isset($expectedBySku[$sku])) {
                continue;
            }

            $this->assertArrayHasKey('__group_id', $row, 'Missing __group_id for SKU: ' . $sku);
            $this->assertIsString($row['__group_id']);
            $this->assertNotSame('', $row['__group_id']);

            $groupId = $row['__group_id'];

            $this->assertMatchesRegularExpression(
                '/^\d+(::.+)?$/',
                $groupId,
                'Unexpected __group_id format for SKU: ' . $sku
            );

            if (strpos($groupId, '::') !== false) {
                [$parentId, $groupValue] = explode('::', $groupId, 2);

                $this->assertNotSame('', $parentId, 'Parent id part should not be empty for SKU: ' . $sku);
                $this->assertSame($expectedBySku[$sku], $groupValue, 'Unexpected grouped value for SKU: ' . $sku);
            }

            $asserted++;
        }

        $this->assertGreaterThan(0, $asserted, 'Expected at least one configurable child with __group_id asserted.');

        $this->groupIdProvider->reset();
        $this->parentRelationsContext->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_text_swatch_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_visual_swatch_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_product_two_swatches_attributes.php
     */
    public function testGetDataForConfigurableProductsUsingTextSwatchAttribute(): void
    {
        $specification = $this->specificationBuilder->build([
            'groupBySourceFieldName' => 'text_swatch_attribute'
        ]);

        $products = $this->getProducts->get($specification);

        $childIds = [];
        foreach ($products as $product) {
            /** @var Product|null $productModel */
            $productModel = $product['product_model'] ?? null;

            if ($productModel && in_array($productModel->getTypeId(), ['simple', 'virtual'], true)) {
                $childIds[] = (int)$productModel->getId();
            }
        }

        $this->parentRelationsContext->buildContext($childIds, $specification);

        $data = $this->groupIdProvider->getData($products, $specification);

        $this->assertNotEmpty($data);

        $asserted = 0;

        foreach ($data as $row) {
            /** @var Product|null $productModel */
            $productModel = $row['product_model'] ?? null;

            if (!$productModel || $productModel->getTypeId() !== 'simple') {
                continue;
            }

            $sku = $productModel->getSku();

            if (strpos($sku, 'simple_') !== 0) {
                continue;
            }

            $this->assertArrayHasKey('__group_id', $row, 'Missing __group_id for SKU: ' . $sku);
            $this->assertIsString($row['__group_id']);
            $this->assertNotSame('', $row['__group_id']);

            $groupId = $row['__group_id'];

            // Valid formats:
            //   parentId
            //   parentId::Option 1
            $this->assertStringContainsString('::', $groupId, 'Expected __group_id to contain "::" for SKU: ' . $sku);
            $this->assertMatchesRegularExpression(
                '/^\d+(::.+)?$/',
                $groupId,
                'Unexpected __group_id format for SKU: ' . $sku
            );

            $label = $productModel->getAttributeText('text_swatch_attribute');
            if (!empty($label) && strpos($groupId, '::') !== false) {
                [$parentId, $groupValue] = explode('::', $groupId, 2);

                $this->assertNotSame('', $parentId, 'Parent id part should not be empty for SKU: ' . $sku);
                $this->assertSame($label, $groupValue, 'Unexpected group value for SKU: ' . $sku);
            }

            $asserted++;
        }

        $this->assertGreaterThan(0, $asserted, 'Expected at least one simple child product to be asserted.');

        $this->groupIdProvider->reset();
        $this->parentRelationsContext->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_color_attribute_select.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_size_attribute_select.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products_for_selected_options.php
     */
    public function testGetDataWithoutParents(): void
    {
        $specification = $this->specificationBuilder->build([
            'groupBySourceFieldName' => 'athos_color'
        ]);

        $products = $this->getProducts->get($specification);

        $data = $this->groupIdProvider->getData($products, $specification);

        $this->assertNotEmpty($data);

        foreach ($data as $row) {
            /** @var Product|null $productModel */
            $productModel = $row['product_model'] ?? null;

            if (!$productModel || $productModel->getTypeId() !== 'simple') {
                continue;
            }

            $this->assertArrayHasKey('__group_id', $row, 'Missing __group_id for SKU: ' . $productModel->getSku());
            $this->assertSame((string)$productModel->getId(), $row['__group_id']);
        }

        $this->groupIdProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_color_attribute_select.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_size_attribute_select.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products_for_selected_options.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable_product_for_selected_options.php
     */
    public function testIgnoreField(): void
    {
        $specification = $this->specificationBuilder->build([
            'groupBySourceFieldName' => 'athos_color',
            'ignoreFields' => ['__group_id'],
        ]);

        $products = $this->getProducts->get($specification);

        $childIds = [];
        foreach ($products as $product) {
            /** @var Product|null $productModel */
            $productModel = $product['product_model'] ?? null;

            if ($productModel && in_array($productModel->getTypeId(), ['simple', 'virtual'], true)) {
                $childIds[] = (int)$productModel->getId();
            }
        }

        $this->parentRelationsContext->buildContext($childIds, $specification);

        $result = $this->groupIdProvider->getData($products, $specification);

        foreach ($result as $item) {
            $this->assertArrayNotHasKey('__group_id', $item);
        }

        $this->groupIdProvider->reset();
        $this->parentRelationsContext->reset();
    }
}
