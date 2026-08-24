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
use Magento\Catalog\Api\Data\ProductLinkInterface;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for grouped parent delete through catalog_product_delete_after_done.
 *
 * Verifies that deleting a grouped parent updates both:
 *  - the grouped parent indexing entity (target_id = parent_id)
 *  - child indexing entities linked through target_parent_id = parent_id
 *
 * @magentoDbIsolation enabled
 * @covers \AthosCommerce\Feed\Observer\Product\DeleteObserver
 * @covers \AthosCommerce\Feed\Observer\BaseProductObserver
 * @covers \AthosCommerce\Feed\Service\Action\SetIndexingEntitiesToUpdateAction
 * @covers \AthosCommerce\Feed\Service\Action\SetIndexingEntitiesToDeleteAction
 */
class GroupedDeleteObserverTest extends TestCase
{
    use IndexingEntitiesTrait;

    private const SITE_ID_PREFIX = 'test-grouped-delete-observer-';

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
     * Deleting a grouped parent must route both the parent and linked child
     * indexing entities to the same next_action/is_indexable outcome.
     *
     * @dataProvider groupedProductDeleteDataProvider
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_OnGroupedProductDelete_UpdatesParentAndChildIndexingEntities(
        int $status,
        int $visibility,
        string $initialLastAction,
        bool $initialIsIndexable,
        string $expectedNextAction,
        bool $expectedIsIndexable
    ): void {
        [$parentProduct, $childProduct] = $this->createAndSaveGroupedProduct($status, $visibility);
        $parentId = (int)$parentProduct->getId();
        $childId = (int)$childProduct->getId();

        $this->createIndexingEntityForProduct($parentId, $initialLastAction, $initialIsIndexable);
        $this->createIndexingEntityForProduct($childId, $initialLastAction, $initialIsIndexable, $parentId);

        /** @var EventManagerInterface $eventManager */
        $eventManager = $this->objectManager->get(EventManagerInterface::class);
        $eventManager->dispatch('catalog_product_delete_after_done', ['product' => $parentProduct]);

        $parentEntity = $this->getIndexingEntityByTargetIdAndParentId($parentId, null);
        $childEntity = $this->getIndexingEntityByTargetIdAndParentId($childId, $parentId);

        $this->assertNotNull($parentEntity, sprintf('No parent IndexingEntity found for grouped id=%d', $parentId));
        $this->assertNotNull($childEntity, sprintf('No child IndexingEntity found for child id=%d parent=%d', $childId, $parentId));

        $this->assertSame($expectedNextAction, $parentEntity->getNextAction(), 'Parent next_action mismatch');
        $this->assertSame($expectedIsIndexable, $parentEntity->getIsIndexable(), 'Parent is_indexable mismatch');
        $this->assertSame($expectedNextAction, $childEntity->getNextAction(), 'Child next_action mismatch');
        $this->assertSame($expectedIsIndexable, $childEntity->getIsIndexable(), 'Child is_indexable mismatch');
    }

    /**
     * When live indexing is disabled the grouped parent delete must leave both the
     * parent and child indexing entities unchanged.
     *
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 0
     */
    public function testExecute_WhenLiveIndexingDisabled_LeavesGroupedParentAndChildEntitiesUnchanged(): void
    {
        [$parentProduct, $childProduct] = $this->createAndSaveGroupedProduct(
            Status::STATUS_ENABLED,
            Visibility::VISIBILITY_BOTH
        );
        $parentId = (int)$parentProduct->getId();
        $childId = (int)$childProduct->getId();

        $this->createIndexingEntityForProduct($parentId, Actions::NO_ACTION, false);
        $this->createIndexingEntityForProduct($childId, Actions::NO_ACTION, false, $parentId);

        /** @var EventManagerInterface $eventManager */
        $eventManager = $this->objectManager->get(EventManagerInterface::class);
        $eventManager->dispatch('catalog_product_delete_after_done', ['product' => $parentProduct]);

        $parentEntity = $this->getIndexingEntityByTargetIdAndParentId($parentId, null);
        $childEntity = $this->getIndexingEntityByTargetIdAndParentId($childId, $parentId);

        $this->assertNotNull($parentEntity);
        $this->assertNotNull($childEntity);
        $this->assertSame(Actions::NO_ACTION, $parentEntity->getNextAction());
        $this->assertFalse($parentEntity->getIsIndexable());
        $this->assertSame(Actions::NO_ACTION, $childEntity->getNextAction());
        $this->assertFalse($childEntity->getIsIndexable());
    }

    public function groupedProductDeleteDataProvider(): array
    {
        return [
            'enabled_visibility_both_indexable_expects_upsert' => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_BOTH,
                'initialLastAction' => Actions::UPSERT,
                'initialIsIndexable' => true,
                'expectedNextAction' => Actions::UPSERT,
                'expectedIsIndexable' => true,
            ],
            'enabled_visibility_in_catalog_indexable_expects_upsert' => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_IN_CATALOG,
                'initialLastAction' => Actions::UPSERT,
                'initialIsIndexable' => true,
                'expectedNextAction' => Actions::UPSERT,
                'expectedIsIndexable' => true,
            ],
            'enabled_visibility_in_search_indexable_expects_upsert' => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_IN_SEARCH,
                'initialLastAction' => Actions::UPSERT,
                'initialIsIndexable' => true,
                'expectedNextAction' => Actions::UPSERT,
                'expectedIsIndexable' => true,
            ],
            'enabled_visibility_both_not_indexable_skipped' => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_BOTH,
                'initialLastAction' => Actions::NO_ACTION,
                'initialIsIndexable' => false,
                'expectedNextAction' => Actions::NO_ACTION,
                'expectedIsIndexable' => false,
            ],
            'disabled_visibility_both_last_upsert_expects_delete' => [
                'status' => Status::STATUS_DISABLED,
                'visibility' => Visibility::VISIBILITY_BOTH,
                'initialLastAction' => Actions::UPSERT,
                'initialIsIndexable' => true,
                'expectedNextAction' => Actions::DELETE,
                'expectedIsIndexable' => true,
            ],
            'disabled_visibility_both_last_no_action_expects_no_action' => [
                'status' => Status::STATUS_DISABLED,
                'visibility' => Visibility::VISIBILITY_BOTH,
                'initialLastAction' => Actions::NO_ACTION,
                'initialIsIndexable' => true,
                'expectedNextAction' => Actions::NO_ACTION,
                'expectedIsIndexable' => false,
            ],
            'enabled_not_visible_last_upsert_expects_delete' => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
                'initialLastAction' => Actions::UPSERT,
                'initialIsIndexable' => true,
                'expectedNextAction' => Actions::DELETE,
                'expectedIsIndexable' => true,
            ],
            'enabled_not_visible_last_no_action_expects_no_action' => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
                'initialLastAction' => Actions::NO_ACTION,
                'initialIsIndexable' => false,
                'expectedNextAction' => Actions::NO_ACTION,
                'expectedIsIndexable' => false,
            ],
            'disabled_not_visible_last_upsert_expects_delete' => [
                'status' => Status::STATUS_DISABLED,
                'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
                'initialLastAction' => Actions::UPSERT,
                'initialIsIndexable' => true,
                'expectedNextAction' => Actions::DELETE,
                'expectedIsIndexable' => true,
            ],
        ];
    }

    /**
     * @return array{0: Product, 1: Product}
     */
    private function createAndSaveGroupedProduct(int $status, int $visibility): array
    {
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        /** @var ProductLinkInterfaceFactory $productLinkFactory */
        $productLinkFactory = $this->objectManager->get(ProductLinkInterfaceFactory::class);

        /** @var Product $childProduct */
        $childProduct = $this->objectManager->create(Product::class);
        $childProduct->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId(4)
            ->setName('Grouped Delete Child ' . uniqid('', true))
            ->setSku('athos_grouped_delete_child_' . uniqid('', true))
            ->setPrice(10)
            ->setStatus(Status::STATUS_ENABLED)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStockData(['qty' => 10, 'is_in_stock' => 1, 'manage_stock' => 0])
            ->setWebsiteIds([$storeManager->getStore()->getWebsiteId()]);
        $childProduct = $productRepository->save($childProduct);

        /** @var Product $parentProduct */
        $parentProduct = $this->objectManager->create(Product::class);
        $parentProduct->setTypeId(Grouped::TYPE_CODE)
            ->setAttributeSetId(4)
            ->setName('Grouped Delete Parent ' . uniqid('', true))
            ->setSku('athos_grouped_delete_parent_' . uniqid('', true))
            ->setStatus($status)
            ->setVisibility($visibility)
            ->setStockData(['is_in_stock' => 1, 'manage_stock' => 0])
            ->setWebsiteIds([$storeManager->getStore()->getWebsiteId()]);

        /** @var ProductLinkInterface $productLink */
        $productLink = $productLinkFactory->create();
        $productLink->setSku($parentProduct->getSku())
            ->setLinkType('associated')
            ->setLinkedProductSku($childProduct->getSku())
            ->setLinkedProductType($childProduct->getTypeId())
            ->getExtensionAttributes()
            ->setQty(1);

        $parentProduct->setProductLinks([$productLink]);
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
