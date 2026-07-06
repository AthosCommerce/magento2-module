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
use AthosCommerce\Feed\Model\Feed\DataProvider\EntityIdProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use AthosCommerce\Feed\Model\ItemsGenerator;
use Magento\Catalog\Model\Product;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EntityIdProviderTest extends TestCase
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
     * @var EntityIdProvider
     */
    private $entityIdProvider;
    /**
     * @var ContextManagerInterface
     */
    private $contextManager;
    /**
     * @var ItemsGenerator
     */
    private $itemsGenerator;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->getProducts = $this->objectManager->get(GetProducts::class);
        $this->entityIdProvider = $this->objectManager->get(EntityIdProvider::class);
        $this->contextManager = $this->objectManager->get(ContextManagerInterface::class);
        $this->itemsGenerator = $this->objectManager->get(ItemsGenerator::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     *
     * @throws \Exception
     */
    public function testGetDataSimpleProducts(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        $config = [
            'athoscommerce_simple_1',
            'athoscommerce_simple_2',
        ];

        foreach ($data as $item) {
            /** @var Product|null $productModel */
            $productModel = $item['product_model'] ?? null;
            if (!$productModel instanceof Product) {
                continue;
            }

            if (!in_array($productModel->getSku(), $config, true)) {
                continue;
            }

            $this->assertArrayHasKey('entity_id', $item);
            $this->assertSame((string)$productModel->getId(), $item['entity_id']);
        }

        $this->contextManager->resetContext();
        $this->entityIdProvider->reset();
        $this->itemsGenerator->resetDataProviders($specification);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_virtual_products.php
     *
     * @throws \Exception
     */
    public function testGetDataVirtualProducts(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        $config = [
            'athoscommerce_virtual_1',
            'athoscommerce_virtual_2',
        ];

        foreach ($data as $item) {
            /** @var Product|null $productModel */
            $productModel = $item['product_model'] ?? null;
            if (!$productModel instanceof Product) {
                continue;
            }

            if (!in_array($productModel->getSku(), $config, true)) {
                continue;
            }

            $this->assertArrayHasKey('entity_id', $item);
            $this->assertSame((string)$productModel->getId(), $item['entity_id']);
        }

        $this->contextManager->resetContext();
        $this->entityIdProvider->reset();
        $this->itemsGenerator->resetDataProviders($specification);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/entity_id_provider_configurable_products.php
     * @throws \Exception
     */
    public function testGetDataForChildRowsBelongingToParent(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        foreach ($data as &$item) {
            /** @var Product|null $productModel */
            $productModel = $item['product_model'] ?? null;
            if (!$productModel instanceof Product) {
                continue;
            }

            if (in_array($productModel->getSku(), [
                'athos_entity_simple_1',
                'athos_entity_simple_2',
            ], true)) {
                $item[Constant::IS_BELONG_TO_PARENT_KEY] = true;
            }
        }
        unset($item);

        $result = $this->entityIdProvider->getData($data, $specification);

        $this->assertEntityIdHasParentPrefix($result, 'athos_entity_simple_1');
        $this->assertEntityIdHasParentPrefix($result, 'athos_entity_simple_2');

        $this->contextManager->resetContext();
        $this->entityIdProvider->reset();
        $this->itemsGenerator->resetDataProviders($specification);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/entity_id_provider_configurable_products.php
     * @throws \Exception
     */
    public function testGetDataForChildRowsNotBelongingToParentReturnsChildId(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        foreach ($data as $item) {
            /** @var Product|null $productModel */
            $productModel = $item['product_model'] ?? null;
            if (!$productModel instanceof Product) {
                continue;
            }

            if (!in_array($productModel->getSku(), [
                'athos_entity_simple_1',
                'athos_entity_simple_2',
            ], true)) {
                continue;
            }

            $this->assertArrayHasKey('entity_id', $item);
            $this->assertSame((string)$productModel->getId(), $item['entity_id']);
        }

        $this->contextManager->resetContext();
        $this->entityIdProvider->reset();
        $this->itemsGenerator->resetDataProviders($specification);
    }

    private function assertEntityIdHasParentPrefix(array $items, string $childSku): void
    {
        foreach ($items as $item) {
            $rowChildSku = $item['child_sku'] ?? null;
            if ($rowChildSku !== $childSku) {
                continue;
            }

            $this->assertArrayHasKey('entity_id', $item);
            $this->assertMatchesRegularExpression(
                '/^\d+_\d+$/',
                (string)$item['entity_id'],
                print_r($item, true)
            );

            $this->assertArrayHasKey('__parent_id', $item, print_r($item, true));

            $parts = explode('_', (string)$item['entity_id']);
            $this->assertCount(2, $parts);
            $this->assertSame((string)$item['__parent_id'], $parts[0]);

            return;
        }

        $this->fail(sprintf('Child row for child_sku "%s" was not found', $childSku));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products.php
     * @throws \Exception
     */
    public function testGetDataForGroupedChildRowsBelongingToParent(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);

        foreach ($data as &$item) {
            $candidateSku = $item['child_sku'] ?? $item['sku'] ?? null;
            if (in_array($candidateSku, [
                'athoscommerce_grouped_test_simple_1000',
                'athoscommerce_grouped_test_simple_1001',
            ], true)) {
                $item[Constant::IS_BELONG_TO_PARENT_KEY] = true;
            }
        }
        unset($item);

        $result = $this->entityIdProvider->getData($data, $specification);

        $this->assertGroupedRowEntityIdMatchesGroupId($result, 'athoscommerce_grouped_test_simple_1000', true);
        $this->assertGroupedRowEntityIdMatchesGroupId($result, 'athoscommerce_grouped_test_simple_1001', true);

        $this->contextManager->resetContext();
        $this->entityIdProvider->reset();
        $this->itemsGenerator->resetDataProviders($specification);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products.php
     * @throws \Exception
     */
    public function testGetDataForGroupedChildRowsNotBelongingToParentReturnsChildId(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->contextManager->setContextFromSpecification($specification);

        $items = $this->getProducts->getCollectionItems($specification);
        $data = $this->itemsGenerator->generate($items, $specification);
        $result = $this->entityIdProvider->getData($data, $specification);

        $this->assertGroupedRowEntityIdMatchesGroupId($result, 'athoscommerce_grouped_test_simple_1010', false);
        $this->assertGroupedRowEntityIdMatchesGroupId($result, 'athoscommerce_grouped_test_simple_1011', false);

        $this->contextManager->resetContext();
        $this->entityIdProvider->reset();
        $this->itemsGenerator->resetDataProviders($specification);
    }

    private function assertGroupedRowEntityIdMatchesGroupId(
        array $items,
        string $childSku,
        bool $expectedBelongsToParent
    ): void {
        foreach ($items as $item) {
            $candidateSku = $item['child_sku'] ?? $item['sku'] ?? null;
            if ($candidateSku !== $childSku) {
                continue;
            }

            $this->assertArrayHasKey('entity_id', $item, print_r($item, true));
            $this->assertArrayHasKey('__group_id', $item, print_r($item, true));

            $this->assertSame(
                $expectedBelongsToParent,
                (bool)($item[Constant::IS_BELONG_TO_PARENT_KEY] ?? false),
                print_r($item, true)
            );

            $this->assertTrue(
                (bool)($item[Constant::IS_STANDALONE_PRODUCT_KEY] ?? false),
                print_r($item, true)
            );

            $this->assertSame(
                (string)$item['__group_id'],
                (string)$item['entity_id'],
                print_r($item, true)
            );

            return;
        }

        $this->fail(sprintf('Grouped row for sku "%s" was not found', $childSku));
    }
}
