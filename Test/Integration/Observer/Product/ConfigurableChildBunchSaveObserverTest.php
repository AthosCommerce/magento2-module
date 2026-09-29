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

namespace AthosCommerce\Feed\Test\Integration\Observer\Product;

use AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface;
use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Model\ResourceModel\IndexingEntity as IndexingEntityResourceModel;
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Observer\Product\BunchSaveObserver;
use AthosCommerce\Feed\Test\Integration\Traits\IndexingEntitiesTrait;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as ConfigurableOptionsFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for configurable child import handling in BunchSaveObserver.
 *
 * Uses direct observer execution to avoid unrelated core import observers that
 * expect a full importer context during event dispatch.
 *
 * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_attribute.php
 * @magentoDbIsolation enabled
 * @covers \AthosCommerce\Feed\Observer\Product\BunchSaveObserver
 * @covers \AthosCommerce\Feed\Service\Provider\ProductNextActionProvider
 */
class ConfigurableChildBunchSaveObserverTest extends TestCase
{
    use IndexingEntitiesTrait;

    private const SITE_ID_PREFIX = 'test-configurable-child-bunch-observer-';

    private ?ObjectManagerInterface $objectManager = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->cleanIndexingEntities(self::SITE_ID_PREFIX . '%');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanIndexingEntities(self::SITE_ID_PREFIX . '%');
    }

    /**
     * @dataProvider visibleParentDataProvider
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_OnConfigurableChildImport_UsesUpsertWhenParentIsVisible(
        int $parentVisibility
    ): void {
        [$parentProduct, $childProduct] = $this->createAndSaveConfigurableProduct(
            Status::STATUS_ENABLED,
            $parentVisibility
        );
        $parentId = (int)$parentProduct->getId();
        $childId = (int)$childProduct->getId();

        $entity = $this->createIndexingEntityForProduct($childId, Actions::NO_ACTION, false);
        $entity->setTargetParentId($parentId);

        /** @var IndexingEntityResourceModel $resourceModel */
        $resourceModel = $this->objectManager->get(IndexingEntityResourceModel::class);
        $resourceModel->save($entity);

        /** @var BunchSaveObserver $observer */
        $observer = $this->objectManager->get(BunchSaveObserver::class);
        $observer->execute(new Observer([
            'event' => new Event([
                'bunch' => [
                    ['sku' => $childProduct->getSku()],
                ],
            ]),
        ]));

        $updatedEntity = $this->getIndexingEntityByTargetId($childId);

        $this->assertNotNull($updatedEntity);
        $this->assertSame(Actions::UPSERT, $updatedEntity->getNextAction());
        $this->assertTrue($updatedEntity->getIsIndexable());
    }

    public function visibleParentDataProvider(): array
    {
        return [
            'parent_visibility_both' => [
                'parentVisibility' => Visibility::VISIBILITY_BOTH,
            ],
            'parent_visibility_in_catalog' => [
                'parentVisibility' => Visibility::VISIBILITY_IN_CATALOG,
            ],
            'parent_visibility_in_search' => [
                'parentVisibility' => Visibility::VISIBILITY_IN_SEARCH,
            ],
        ];
    }

    /**
     * @return array{0: Product, 1: Product}
     */
    private function createAndSaveConfigurableProduct(int $status, int $visibility): array
    {
        /** @var EavConfig $eavConfig */
        $eavConfig = $this->objectManager->get(EavConfig::class);
        $attribute = $eavConfig->getAttribute(Product::ENTITY, 'test_configurable');

        $options = $attribute->getOptions();
        array_shift($options);

        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);

        $firstOption = reset($options);

        /** @var Product $childProduct */
        $childProduct = $this->objectManager->create(Product::class);
        $childProduct->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId(4)
            ->setName('Import Configurable Child ' . uniqid('', true))
            ->setSku('athos_import_cfg_child_' . uniqid('', true))
            ->setPrice(10)
            ->setStatus(Status::STATUS_ENABLED)
            ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
            ->setStockData(['qty' => 10, 'is_in_stock' => 1, 'manage_stock' => 0])
            ->setWebsiteIds([$storeManager->getStore()->getWebsiteId()])
            ->setData($attribute->getAttributeCode(), $firstOption->getValue());

        $childProduct = $productRepository->save($childProduct);

        /** @var ConfigurableOptionsFactory $optionsFactory */
        $optionsFactory = $this->objectManager->get(ConfigurableOptionsFactory::class);
        $configurableAttributesData = [
            [
                'attribute_id' => $attribute->getId(),
                'code' => $attribute->getAttributeCode(),
                'label' => $attribute->getStoreLabel(),
                'position' => '0',
                'values' => [
                    [
                        'label' => 'test',
                        'attribute_id' => $attribute->getId(),
                        'value_index' => $firstOption->getValue(),
                    ],
                ],
            ],
        ];

        $configurableOptions = $optionsFactory->create($configurableAttributesData);

        /** @var Product $parentProduct */
        $parentProduct = $this->objectManager->create(Product::class);
        $extensionAttributes = $parentProduct->getExtensionAttributes();
        $extensionAttributes->setConfigurableProductOptions($configurableOptions);
        $extensionAttributes->setConfigurableProductLinks([(int)$childProduct->getId()]);
        $parentProduct->setExtensionAttributes($extensionAttributes);

        $parentProduct->setTypeId(Configurable::TYPE_CODE)
            ->setAttributeSetId(4)
            ->setName('Import Configurable Parent ' . uniqid('', true))
            ->setSku('athos_import_cfg_parent_' . uniqid('', true))
            ->setStatus($status)
            ->setVisibility($visibility)
            ->setStockData(['is_in_stock' => 1, 'manage_stock' => 0])
            ->setWebsiteIds([$storeManager->getStore()->getWebsiteId()])
            ->setPrice(10);

        $parentProduct = $productRepository->save($parentProduct);

        return [$parentProduct, $childProduct];
    }

    private function createIndexingEntityForProduct(
        int $productId,
        string $lastAction,
        bool $isIndexable
    ): IndexingEntity {
        /** @var IndexingEntity $entity */
        $entity = $this->objectManager->create(IndexingEntity::class);
        $entity->setTargetEntityType(Constants::PRODUCT_KEY);
        $entity->setTargetId($productId);
        $entity->setSiteId(self::SITE_ID_PREFIX . random_int(0, 999999999));
        $entity->setNextAction(Actions::NO_ACTION);
        $entity->setLastAction($lastAction);
        $entity->setIsIndexable($isIndexable);

        /** @var IndexingEntityResourceModel $resourceModel */
        $resourceModel = $this->objectManager->get(IndexingEntityResourceModel::class);
        $resourceModel->save($entity);

        return $entity;
    }

    private function getIndexingEntityByTargetId(int $targetId): ?IndexingEntity
    {
        /** @var SearchCriteriaBuilderFactory $factory */
        $factory = $this->objectManager->get(SearchCriteriaBuilderFactory::class);
        $searchCriteria = $factory->create()
            ->addFilter(IndexingEntity::TARGET_ID, $targetId)
            ->create();

        /** @var IndexingEntityRepositoryInterface $repository */
        $repository = $this->objectManager->get(IndexingEntityRepositoryInterface::class);
        $items = $repository->getList($searchCriteria)->getItems();

        return !empty($items) ? reset($items) : null;
    }
}
