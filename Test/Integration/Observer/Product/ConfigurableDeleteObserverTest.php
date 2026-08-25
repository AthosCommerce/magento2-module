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
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for configurable parent delete through catalog_product_delete_after_done.
 *
 * Verifies that deleting a configurable parent updates both:
 *  - the configurable parent indexing entity (target_id = parent_id, target_parent_id = null)
 *  - child indexing entities linked through target_parent_id = parent_id
 *
 * The event is dispatched manually because the product is already removed from the
 * database when the observer fires in production.
 *
 * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_attribute.php
 * @magentoDbIsolation enabled
 * @covers \AthosCommerce\Feed\Observer\Product\DeleteObserver
 * @covers \AthosCommerce\Feed\Observer\BaseProductObserver
 * @covers \AthosCommerce\Feed\Service\Action\SetIndexingEntitiesToUpdateAction
 * @covers \AthosCommerce\Feed\Service\Action\SetIndexingEntitiesToDeleteAction
 */
class ConfigurableDeleteObserverTest extends TestCase
{
    use IndexingEntitiesTrait;

    private const SITE_ID_PREFIX = 'test-configurable-delete-observer-';

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
     * Deleting a configurable parent must route both the parent and linked child
     * indexing entities to the same next_action/is_indexable outcome.
     *
     * DeleteObserver uses forceIndexable=false, so:
     *  - entities with is_indexable=false are skipped on the UPSERT path
     *  - on the DELETE path SetIndexingEntitiesToDeleteAction applies
     *    (last_action=NO_ACTION → next_action=NO_ACTION, is_indexable=false)
     *
     * @dataProvider configurableProductDeleteDataProvider
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_OnConfigurableProductDelete_UpdatesParentAndChildIndexingEntities(
        int $status,
        int $visibility,
        string $initialLastAction,
        bool $initialIsIndexable,
        string $expectedNextAction,
        bool $expectedIsIndexable
    ): void {
        [$parentProduct, $childProduct] = $this->createAndSaveConfigurableProduct($status, $visibility);
        $parentId = (int)$parentProduct->getId();
        $childId  = (int)$childProduct->getId();

        $this->createIndexingEntityForProduct($parentId, $initialLastAction, $initialIsIndexable);
        $this->createIndexingEntityForProduct($childId, $initialLastAction, $initialIsIndexable, $parentId);

        /** @var EventManagerInterface $eventManager */
        $eventManager = $this->objectManager->get(EventManagerInterface::class);
        $eventManager->dispatch('catalog_product_delete_after_done', ['product' => $parentProduct]);

        $parentEntity = $this->getIndexingEntityByTargetIdAndParentId($parentId, null);
        $childEntity  = $this->getIndexingEntityByTargetIdAndParentId($childId, $parentId);

        $this->assertNotNull(
            $parentEntity,
            sprintf('No parent IndexingEntity found for configurable id=%d', $parentId)
        );
        $this->assertNotNull(
            $childEntity,
            sprintf('No child IndexingEntity found for child id=%d parent=%d', $childId, $parentId)
        );

        $this->assertSame($expectedNextAction, $parentEntity->getNextAction(), 'Parent next_action mismatch');
        $this->assertSame($expectedIsIndexable, $parentEntity->getIsIndexable(), 'Parent is_indexable mismatch');
        $this->assertSame($expectedNextAction, $childEntity->getNextAction(), 'Child next_action mismatch');
        $this->assertSame($expectedIsIndexable, $childEntity->getIsIndexable(), 'Child is_indexable mismatch');
    }

    /**
     * When live indexing is disabled the configurable parent delete must leave both
     * the parent and child indexing entities unchanged.
     *
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 0
     */
    public function testExecute_WhenLiveIndexingDisabled_LeavesConfigurableParentAndChildEntitiesUnchanged(): void
    {
        [$parentProduct, $childProduct] = $this->createAndSaveConfigurableProduct(
            Status::STATUS_ENABLED,
            Visibility::VISIBILITY_BOTH
        );
        $parentId = (int)$parentProduct->getId();
        $childId  = (int)$childProduct->getId();

        $this->createIndexingEntityForProduct($parentId, Actions::NO_ACTION, false);
        $this->createIndexingEntityForProduct($childId, Actions::NO_ACTION, false, $parentId);

        /** @var EventManagerInterface $eventManager */
        $eventManager = $this->objectManager->get(EventManagerInterface::class);
        $eventManager->dispatch('catalog_product_delete_after_done', ['product' => $parentProduct]);

        $parentEntity = $this->getIndexingEntityByTargetIdAndParentId($parentId, null);
        $childEntity  = $this->getIndexingEntityByTargetIdAndParentId($childId, $parentId);

        $this->assertNotNull($parentEntity);
        $this->assertNotNull($childEntity);
        $this->assertSame(Actions::NO_ACTION, $parentEntity->getNextAction());
        $this->assertFalse($parentEntity->getIsIndexable());
        $this->assertSame(Actions::NO_ACTION, $childEntity->getNextAction());
        $this->assertFalse($childEntity->getIsIndexable());
    }

    public function configurableProductDeleteDataProvider(): array
    {
        return [
            // --- UPSERT path (enabled + visible, already indexed) ---
            'enabled_visibility_both_indexable_expects_upsert' => [
                'status'              => Status::STATUS_ENABLED,
                'visibility'          => Visibility::VISIBILITY_BOTH,
                'initialLastAction'   => Actions::UPSERT,
                'initialIsIndexable'  => true,
                'expectedNextAction'  => Actions::UPSERT,
                'expectedIsIndexable' => true,
            ],
            'enabled_visibility_in_catalog_indexable_expects_upsert' => [
                'status'              => Status::STATUS_ENABLED,
                'visibility'          => Visibility::VISIBILITY_IN_CATALOG,
                'initialLastAction'   => Actions::UPSERT,
                'initialIsIndexable'  => true,
                'expectedNextAction'  => Actions::UPSERT,
                'expectedIsIndexable' => true,
            ],
            'enabled_visibility_in_search_indexable_expects_upsert' => [
                'status'              => Status::STATUS_ENABLED,
                'visibility'          => Visibility::VISIBILITY_IN_SEARCH,
                'initialLastAction'   => Actions::UPSERT,
                'initialIsIndexable'  => true,
                'expectedNextAction'  => Actions::UPSERT,
                'expectedIsIndexable' => true,
            ],
            // --- UPSERT path skipped (enabled + visible, not yet indexed) ---
            'enabled_visibility_both_not_indexable_skipped' => [
                'status'              => Status::STATUS_ENABLED,
                'visibility'          => Visibility::VISIBILITY_BOTH,
                'initialLastAction'   => Actions::NO_ACTION,
                'initialIsIndexable'  => false,
                'expectedNextAction'  => Actions::NO_ACTION,
                'expectedIsIndexable' => false,
            ],
            // --- DELETE path (disabled or not-visible), previously indexed ---
            'disabled_visibility_both_last_upsert_expects_delete' => [
                'status'              => Status::STATUS_DISABLED,
                'visibility'          => Visibility::VISIBILITY_BOTH,
                'initialLastAction'   => Actions::UPSERT,
                'initialIsIndexable'  => true,
                'expectedNextAction'  => Actions::DELETE,
                'expectedIsIndexable' => true,
            ],
            'enabled_not_visible_last_upsert_expects_delete' => [
                'status'              => Status::STATUS_ENABLED,
                'visibility'          => Visibility::VISIBILITY_NOT_VISIBLE,
                'initialLastAction'   => Actions::UPSERT,
                'initialIsIndexable'  => true,
                'expectedNextAction'  => Actions::DELETE,
                'expectedIsIndexable' => true,
            ],
            // --- DELETE path resolved to NO_ACTION (never indexed before) ---
            'disabled_visibility_both_last_no_action_expects_no_action' => [
                'status'              => Status::STATUS_DISABLED,
                'visibility'          => Visibility::VISIBILITY_BOTH,
                'initialLastAction'   => Actions::NO_ACTION,
                'initialIsIndexable'  => true,
                'expectedNextAction'  => Actions::NO_ACTION,
                'expectedIsIndexable' => false,
            ],
            'enabled_not_visible_last_no_action_expects_no_action' => [
                'status'              => Status::STATUS_ENABLED,
                'visibility'          => Visibility::VISIBILITY_NOT_VISIBLE,
                'initialLastAction'   => Actions::NO_ACTION,
                'initialIsIndexable'  => false,
                'expectedNextAction'  => Actions::NO_ACTION,
                'expectedIsIndexable' => false,
            ],
            'disabled_not_visible_last_upsert_expects_delete' => [
                'status'              => Status::STATUS_DISABLED,
                'visibility'          => Visibility::VISIBILITY_NOT_VISIBLE,
                'initialLastAction'   => Actions::UPSERT,
                'initialIsIndexable'  => true,
                'expectedNextAction'  => Actions::DELETE,
                'expectedIsIndexable' => true,
            ],
        ];
    }

    /**
     * Creates a configurable parent product with one simple child linked via the
     * test_configurable EAV attribute.  The parent is saved with the supplied
     * status and visibility; the child always uses VISIBILITY_NOT_VISIBLE /
     * STATUS_ENABLED (standard for configurable children).
     *
     * @return array{0: Product, 1: Product}  [parentProduct, childProduct]
     */
    private function createAndSaveConfigurableProduct(int $status, int $visibility): array
    {
        /** @var EavConfig $eavConfig */
        $eavConfig = $this->objectManager->get(EavConfig::class);
        $attribute = $eavConfig->getAttribute(Product::ENTITY, 'test_configurable');

        $options = $attribute->getOptions();
        array_shift($options); // remove the empty placeholder

        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);

        $firstOption = reset($options);

        /** @var Product $childProduct */
        $childProduct = $this->objectManager->create(Product::class);
        $childProduct->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId(4)
            ->setName('Configurable Delete Child ' . uniqid('', true))
            ->setSku('athos_cfg_del_child_' . uniqid('', true))
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
                'code'         => $attribute->getAttributeCode(),
                'label'        => $attribute->getStoreLabel(),
                'position'     => '0',
                'values'       => [
                    [
                        'label'        => 'test',
                        'attribute_id' => $attribute->getId(),
                        'value_index'  => $firstOption->getValue(),
                    ],
                ],
            ],
        ];

        $configurableOptions = $optionsFactory->create($configurableAttributesData);

        /** @var Product $parentProduct */
        $parentProduct = $this->objectManager->create(Product::class);
        $extensionAttributes = $parentProduct->getExtensionAttributes();
        $extensionAttributes->setConfigurableProductOptions($configurableOptions);
        $extensionAttributes->setConfigurableProductLinks([$childProduct->getId()]);
        $parentProduct->setExtensionAttributes($extensionAttributes);

        $parentProduct->setTypeId(Configurable::TYPE_CODE)
            ->setAttributeSetId(4)
            ->setName('Configurable Delete Parent ' . uniqid('', true))
            ->setSku('athos_cfg_del_parent_' . uniqid('', true))
            ->setStatus($status)
            ->setVisibility($visibility)
            ->setStockData(['is_in_stock' => 1, 'manage_stock' => 0])
            ->setWebsiteIds([$storeManager->getStore()->getWebsiteId()]);

        $parentProduct = $productRepository->save($parentProduct);

        return [$parentProduct, $childProduct];
    }

    private function createIndexingEntityForProduct(
        int $productId,
        string $lastAction,
        bool $isIndexable,
        ?int $targetParentId = null
    ): IndexingEntity {
        /** @var IndexingEntity $entity */
        $entity = $this->objectManager->create(IndexingEntity::class);
        $entity->setTargetEntityType(Constants::PRODUCT_KEY);
        $entity->setTargetId($productId);
        $entity->setTargetParentId($targetParentId);
        $entity->setSiteId(self::SITE_ID_PREFIX . random_int(0, 999999999));
        $entity->setNextAction(Actions::NO_ACTION);
        $entity->setLastAction($lastAction);
        $entity->setIsIndexable($isIndexable);

        /** @var IndexingEntityResourceModel $resourceModel */
        $resourceModel = $this->objectManager->get(IndexingEntityResourceModel::class);
        $resourceModel->save($entity);

        return $entity;
    }

    private function getIndexingEntityByTargetIdAndParentId(int $targetId, ?int $targetParentId): ?IndexingEntity
    {
        /** @var SearchCriteriaBuilderFactory $factory */
        $factory = $this->objectManager->get(SearchCriteriaBuilderFactory::class);
        $searchCriteria = $factory->create()
            ->addFilter(IndexingEntity::TARGET_ID, $targetId)
            ->create();

        /** @var IndexingEntityRepositoryInterface $repository */
        $repository = $this->objectManager->get(IndexingEntityRepositoryInterface::class);
        foreach ($repository->getList($searchCriteria)->getItems() as $item) {
            if ($item->getTargetParentId() === $targetParentId) {
                return $item;
            }
        }

        return null;
    }
}
