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
use AthosCommerce\Feed\Model\Feed\DataProvider\GroupIdProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ConfigurableDataProvider;
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
    private const CUSTOM_PARENT_GROUPING_KEY = 'TEST_PARENT_GROUP_001';

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
     * @var ConfigurableDataProvider
     */
    private $configurableDataProvider;

    /**
     * @var ContextManagerInterface
     */
    private $contextManager;

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
        $this->configurableDataProvider = $this->objectManager->get(ConfigurableDataProvider::class);
        $this->contextManager = $this->objectManager->get(ContextManagerInterface::class);
        $this->groupIdProvider = $this->objectManager->get(GroupIdProvider::class);
        $this->parentRelationsContext = $this->objectManager->get(ParentRelationsContext::class);

        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/entity_id_provider_configurable_products.php
     */
    public function testGenerateUsesMagentoParentEntityIdByDefault(): void
    {
        $data = $this->generateFeedData([]);
        $rowsBySku = $this->getRowsByChildSku($data, ['athos_entity_simple_1', 'athos_entity_simple_2']);

        foreach (['athos_entity_simple_1', 'athos_entity_simple_2'] as $childSku) {
            $parentRow = $this->assertSingleRowForContext($rowsBySku, $childSku, 'parent');
            $this->assertSame(
                (string)$parentRow[Constant::RESOLVED_PARENT_ID_KEY],
                (string)$parentRow[Constant::PARENT_ID]
            );
            $this->assertSame(
                (string)$parentRow[Constant::PARENT_ID],
                (string)$parentRow[Constant::GROUP_ID]
            );
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/entity_id_provider_configurable_products.php
     */
    public function testGenerateUsesMagentoParentEntityIdAndOptionValueWhenGroupByIsConfigured(): void
    {
        $data = $this->generateFeedData([
            'groupBySourceFieldName' => 'test_configurable_first',
        ]);
        $rowsBySku = $this->getRowsByChildSku($data, [
            'athos_entity_simple_1',
            'athos_entity_simple_2',
        ]);

        foreach (['athos_entity_simple_1', 'athos_entity_simple_2'] as $childSku) {
            $parentRow = $this->assertSingleRowForContext($rowsBySku, $childSku, 'parent');
            /** @var Product $productModel */
            $productModel = $parentRow['product_model'];
            $expectedParentGroupId = (string)$parentRow[Constant::RESOLVED_PARENT_ID_KEY]
                . '::'
                . (string)$productModel->getAttributeText('test_configurable_first');

            $this->assertSame(
                (string)$parentRow[Constant::RESOLVED_PARENT_ID_KEY],
                (string)$parentRow[Constant::PARENT_ID]
            );
            $this->assertSame($expectedParentGroupId, (string)$parentRow[Constant::GROUP_ID]);
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products_visibility_any_child_visibility_any.php
     */
    public function testGenerateUsesChildEntityIdForStandaloneRowsWhenGroupingByVariantAttribute(): void
    {
        $data = $this->generateFeedData([
            'groupBySourceFieldName' => 'test_configurable_first',
        ]);
        $rowsBySku = $this->getRowsByChildSku($data, [
            'athos_config_test_simple_100',
            'athos_config_test_simple_200',
        ]);

        foreach (['athos_config_test_simple_100', 'athos_config_test_simple_200'] as $childSku) {
            $standaloneRow = $this->assertSingleRowForContext($rowsBySku, $childSku, 'standalone');
            /** @var Product $productModel */
            $productModel = $standaloneRow['product_model'];
            $expectedGroupId = (string)$standaloneRow['entity_id']
                . '::'
                . (string)$productModel->getAttributeText('test_configurable_first');

            $this->assertSame(
                $expectedGroupId,
                (string)$standaloneRow[Constant::GROUP_ID]
            );
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_parent_grouping_key_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/entity_id_provider_configurable_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/entity_id_provider_parent_grouping_key.php
     */
    public function testGenerateUsesConfiguredParentIdentifierWhenGroupByIsBlank(): void
    {
        $data = $this->generateFeedData([
            'parentIdSourceFieldName' => 'test_parent_group_code',
        ]);
        $rowsBySku = $this->getRowsByChildSku($data, ['athos_entity_simple_1', 'athos_entity_simple_2']);

        foreach (['athos_entity_simple_1', 'athos_entity_simple_2'] as $childSku) {
            $parentRow = $this->assertSingleRowForContext($rowsBySku, $childSku, 'parent');

            $this->assertSame(self::CUSTOM_PARENT_GROUPING_KEY, (string)$parentRow[Constant::PARENT_ID]);
            $this->assertSame(self::CUSTOM_PARENT_GROUPING_KEY, (string)$parentRow[Constant::GROUP_ID]);
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_parent_grouping_key_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/entity_id_provider_configurable_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/entity_id_provider_parent_grouping_key.php
     */
    public function testGenerateUsesConfiguredParentIdentifierAndOptionValueWhenBothFieldsAreSet(): void
    {
        $data = $this->generateFeedData([
            'parentIdSourceFieldName' => 'test_parent_group_code',
            'groupBySourceFieldName' => 'test_configurable_first',
        ]);
        $rowsBySku = $this->getRowsByChildSku($data, [
            'athos_entity_simple_1',
            'athos_entity_simple_2',
        ]);

        foreach (['athos_entity_simple_1', 'athos_entity_simple_2'] as $childSku) {
            $parentRow = $this->assertSingleRowForContext($rowsBySku, $childSku, 'parent');
            /** @var Product $productModel */
            $productModel = $parentRow['product_model'];
            $expectedGroupId = self::CUSTOM_PARENT_GROUPING_KEY
                . '::'
                . (string)$productModel->getAttributeText('test_configurable_first');

            $this->assertSame(self::CUSTOM_PARENT_GROUPING_KEY, (string)$parentRow[Constant::PARENT_ID]);
            $this->assertSame($expectedGroupId, (string)$parentRow[Constant::GROUP_ID]);
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products_visibility_any_child_visibility_any.php
     */
    public function testGenerateUsesGenericMagentoAttributeWhenGroupByIsConfigured(): void
    {
        $data = $this->generateFeedData([
            'groupBySourceFieldName' => 'sku',
        ]);
        $rowsBySku = $this->getRowsByChildSku($data, [
            'athos_config_test_simple_100',
            'athos_config_test_simple_200',
        ]);

        foreach (['athos_config_test_simple_100', 'athos_config_test_simple_200'] as $childSku) {
            $parentRow = $this->assertSingleRowForContext($rowsBySku, $childSku, 'parent');
            $standaloneRow = $this->assertSingleRowForContext($rowsBySku, $childSku, 'standalone');

            $this->assertSame(
                (string)$parentRow[Constant::RESOLVED_PARENT_ID_KEY] . '::' . $childSku,
                (string)$parentRow[Constant::GROUP_ID]
            );
            $this->assertSame(
                (string)$standaloneRow['entity_id'] . '::' . $childSku,
                (string)$standaloneRow[Constant::GROUP_ID]
            );
        }
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
        $data = $this->generateFeedData([
            'groupBySourceFieldName' => 'athos_color',
            'ignoreFields' => [Constant::GROUP_ID],
        ]);

        foreach ($data as $item) {
            $this->assertArrayNotHasKey(Constant::GROUP_ID, $item);
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_color_attribute_select.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_size_attribute_select.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products_for_selected_options.php
     */
    public function testGenerateFallsBackToChildIdWhenNoParentRelationExists(): void
    {
        $specification = $this->specificationBuilder->build([
            'groupBySourceFieldName' => 'athos_color',
        ]);
        $products = $this->getProducts->get($specification);
        $data = $this->groupIdProvider->getData($products, $specification);

        foreach ($data as $row) {
            /** @var Product|null $productModel */
            $productModel = $row['product_model'] ?? null;

            if (!$productModel instanceof Product) {
                continue;
            }

            if (!in_array($productModel->getSku(), ['simple-red-m', 'simple-blue-s'], true)) {
                continue;
            }

            $this->assertSame(
                (string)$productModel->getId() . '::' . (string)$productModel->getAttributeText('athos_color'),
                (string)$row[Constant::GROUP_ID]
            );
        }

        $this->groupIdProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_color_attribute_select.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_size_attribute_select.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products_for_selected_options.php
     */
    public function testGenerateUsesGenericMagentoAttributeForStandaloneRowsWithoutParentRelation(): void
    {
        $specification = $this->specificationBuilder->build([
            'groupBySourceFieldName' => 'sku',
        ]);
        $products = $this->getProducts->get($specification);
        $data = $this->groupIdProvider->getData($products, $specification);

        foreach ($data as $row) {
            /** @var Product|null $productModel */
            $productModel = $row['product_model'] ?? null;

            if (!$productModel instanceof Product) {
                continue;
            }

            if (!in_array($productModel->getSku(), ['simple-red-m', 'simple-blue-s'], true)) {
                continue;
            }

            $this->assertSame(
                (string)$productModel->getId() . '::' . $productModel->getSku(),
                (string)$row[Constant::GROUP_ID]
            );
        }

        $this->groupIdProvider->reset();
    }

    /**
     * @param array $specificationData
     * @return array
     */
    private function generateFeedData(array $specificationData): array
    {
        $specification = $this->specificationBuilder->build($specificationData);
        $this->contextManager->setContextFromSpecification($specification);

        try {
            $products = $this->getProducts->get($specification);
            $childIds = $this->getChildIds($products);
            $this->parentRelationsContext->buildContext($childIds, $specification);
            $data = $this->configurableDataProvider->getData($products, $specification);
            $data = $this->groupIdProvider->getData($data, $specification);

            return $data;
        } finally {
            $this->groupIdProvider->reset();
            $this->configurableDataProvider->reset();
            $this->parentRelationsContext->reset();
            $this->contextManager->resetContext();
        }
    }

    /**
     * @param array $products
     * @return int[]
     */
    private function getChildIds(array $products): array
    {
        $childIds = [];

        foreach ($products as $product) {
            /** @var Product|null $productModel */
            $productModel = $product['product_model'] ?? null;

            if (!$productModel instanceof Product) {
                continue;
            }

            if (!in_array($productModel->getTypeId(), ['simple', 'virtual'], true)) {
                continue;
            }

            $childIds[] = (int)$productModel->getId();
        }

        return $childIds;
    }

    /**
     * @param array $rows
     * @param array $expectedBySku
     * @return array
     */
    private function getRowsByChildSku(array $rows, array $expectedBySku): array
    {
        $expectedSkus = array_keys($expectedBySku) === range(0, count($expectedBySku) - 1)
            ? $expectedBySku
            : array_keys($expectedBySku);

        $result = [];
        foreach ($rows as $row) {
            $childSku = (string)($row['child_sku'] ?? $row['sku'] ?? '');
            if (!in_array($childSku, $expectedSkus, true)) {
                continue;
            }

            $context = $this->getRowContext($row);
            $result[$childSku][$context][] = $row;
        }

        return $result;
    }

    /**
     * @param array $rowsBySku
     * @param string $childSku
     * @param string $context
     * @return array
     */
    private function assertSingleRowForContext(array $rowsBySku, string $childSku, string $context): array
    {
        $rows = $rowsBySku[$childSku][$context] ?? [];

        $this->assertCount(
            1,
            $rows,
            sprintf('Expected exactly one %s row for SKU %s', $context, $childSku)
        );

        return $rows[0];
    }

    /**
     * @param array $row
     * @return string
     */
    private function getRowContext(array $row): string
    {
        if (($row[Constant::IS_BELONG_TO_PARENT_KEY] ?? false) === true) {
            return 'parent';
        }

        if (($row[Constant::IS_STANDALONE_PRODUCT_KEY] ?? false) === true) {
            return 'standalone';
        }

        return 'other';
    }
}
