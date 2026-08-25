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
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for catalog_product_delete_after_done → DeleteObserver flow.
 *
 * Verifies that the delete observer correctly updates next_action and is_indexable
 * on the corresponding athoscommerce_indexing_entity row. The event is dispatched
 * manually to avoid coupling to physical product deletion in DB.
 *
 * Key difference from UpdateObserver: forceIndexable is NOT passed (defaults to
 * false), so entities with is_indexable=false and a UPSERT action are skipped.
 *
 * @magentoDbIsolation enabled
 * @covers \AthosCommerce\Feed\Observer\Product\DeleteObserver
 * @covers \AthosCommerce\Feed\Observer\BaseProductObserver
 * @covers \AthosCommerce\Feed\Service\Action\SetIndexingEntitiesToUpdateAction
 * @covers \AthosCommerce\Feed\Service\Action\SetIndexingEntitiesToDeleteAction
 */
class DeleteObserverTest extends TestCase
{
    use IndexingEntitiesTrait;

    private const SITE_ID_PREFIX = 'test-delete-observer-';

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
     * Tests that dispatching catalog_product_delete_after_done updates next_action
     * and is_indexable on the indexing entity according to product status/visibility
     * and the forceIndexable=false constraint of the DeleteObserver.
     *
     * @dataProvider productDeleteDataProvider
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_OnProductDelete_UpdatesNextActionAndIsIndexable(
        int    $status,
        int    $visibility,
        string $initialLastAction,
        bool   $initialIsIndexable,
        string $expectedNextAction,
        bool   $expectedIsIndexable
    ): void {
        $product = $this->createAndSaveTestProduct($status, $visibility);
        $productId = (int)$product->getId();

        $this->createIndexingEntityForProduct($productId, $initialLastAction, $initialIsIndexable);

        // Dispatch the event manually – mirrors what Magento fires after deletion.
        /** @var EventManagerInterface $eventManager */
        $eventManager = $this->objectManager->get(EventManagerInterface::class);
        $eventManager->dispatch('catalog_product_delete_after_done', ['product' => $product]);

        $updatedEntity = $this->getIndexingEntityByTargetId($productId);

        $this->assertNotNull(
            $updatedEntity,
            sprintf('No IndexingEntity found for product id=%d', $productId)
        );
        $this->assertSame(
            $expectedNextAction,
            $updatedEntity->getNextAction(),
            sprintf(
                'next_action mismatch for status=%d visibility=%d',
                $status,
                $visibility
            )
        );
        $this->assertSame(
            $expectedIsIndexable,
            $updatedEntity->getIsIndexable(),
            sprintf(
                'is_indexable mismatch for status=%d visibility=%d',
                $status,
                $visibility
            )
        );
    }

    /**
     * Tests that when live indexing is disabled the observer is a no-op and
     * the indexing entity retains its initial state.
     *
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 0
     */
    public function testExecute_WhenLiveIndexingDisabled_LeavesIndexingEntityUnchanged(): void
    {
        $product = $this->createAndSaveTestProduct(Status::STATUS_ENABLED, Visibility::VISIBILITY_BOTH);
        $productId = (int)$product->getId();

        $this->createIndexingEntityForProduct($productId, Actions::UPSERT, true);

        /** @var EventManagerInterface $eventManager */
        $eventManager = $this->objectManager->get(EventManagerInterface::class);
        $eventManager->dispatch('catalog_product_delete_after_done', ['product' => $product]);

        $entity = $this->getIndexingEntityByTargetId($productId);

        $this->assertNotNull($entity);
        $this->assertSame(
            Actions::NO_ACTION,
            $entity->getNextAction(),
            'next_action must remain NO_ACTION when live indexing is disabled'
        );
        $this->assertTrue(
            $entity->getIsIndexable(),
            'is_indexable must remain unchanged when live indexing is disabled'
        );
    }

    /**
     * Data provider covering all relevant combinations for the DeleteObserver.
     *
     * Observer logic (DeleteObserver):
     *   nextAction = (status != ENABLED || visibility == NOT_VISIBLE) ? DELETE : UPSERT
     *   forceIndexable = false  (DeleteObserver does not force)
     *
     * SetIndexingEntitiesToUpdateAction (forceIndexable=false):
     *   → is_indexable=true  : next_action = UPSERT            (entity updated)
     *   → is_indexable=false : entity SKIPPED (no change)
     *
     * SetIndexingEntitiesToDeleteAction:
     *   → last_action == NO_ACTION : next_action = NO_ACTION, is_indexable = false
     *   → last_action != NO_ACTION : next_action = DELETE       (is_indexable unchanged)
     */
    public function productDeleteDataProvider(): array
    {
        return [
            // ── UPSERT path (enabled + visible product) ────────────────────────
            'enabled_visible_both_indexable_expects_upsert' => [
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
            // forceIndexable=false: non-indexable entity skipped on UPSERT path
            'enabled_visible_both_not_indexable_skipped' => [
                'status'              => Status::STATUS_ENABLED,
                'visibility'          => Visibility::VISIBILITY_BOTH,
                'initialLastAction'   => Actions::NO_ACTION,
                'initialIsIndexable'  => false,
                'expectedNextAction'  => Actions::NO_ACTION,   // unchanged – entity skipped
                'expectedIsIndexable' => false,                // unchanged
            ],
            // ── DELETE path (disabled product) ────────────────────────────────
            'disabled_visibility_both_last_upsert_expects_delete' => [
                'status'              => Status::STATUS_DISABLED,
                'visibility'          => Visibility::VISIBILITY_BOTH,
                'initialLastAction'   => Actions::UPSERT,
                'initialIsIndexable'  => true,
                'expectedNextAction'  => Actions::DELETE,
                'expectedIsIndexable' => true,   // is_indexable unchanged by delete action
            ],
            'disabled_visibility_both_last_no_action_expects_no_action' => [
                'status'              => Status::STATUS_DISABLED,
                'visibility'          => Visibility::VISIBILITY_BOTH,
                'initialLastAction'   => Actions::NO_ACTION,
                'initialIsIndexable'  => true,
                'expectedNextAction'  => Actions::NO_ACTION,
                'expectedIsIndexable' => false,
            ],
            // ── DELETE path (not-visible product) ─────────────────────────────
            'enabled_not_visible_last_upsert_expects_delete' => [
                'status'              => Status::STATUS_ENABLED,
                'visibility'          => Visibility::VISIBILITY_NOT_VISIBLE,
                'initialLastAction'   => Actions::UPSERT,
                'initialIsIndexable'  => true,
                'expectedNextAction'  => Actions::DELETE,
                'expectedIsIndexable' => true,
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
     * Creates and saves a simple product with the given status and visibility.
     * Only used to obtain a real product ID and a populated product model with
     * storeIds – the observer is not expected to act during this first save
     * since no IndexingEntity exists yet.
     */
    private function createAndSaveTestProduct(int $status, int $visibility): Product
    {
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);

        /** @var Product $product */
        $product = $this->objectManager->create(Product::class);
        $product->setTypeId('simple')
            ->setAttributeSetId(4)
            ->setName('DeleteObserver Test ' . uniqid('', true))
            ->setSku('athos_del_obs_' . uniqid('', true))
            ->setPrice(10)
            ->setStatus($status)
            ->setVisibility($visibility)
            ->setStockData(['qty' => 10, 'is_in_stock' => 1, 'manage_stock' => 0])
            ->setWebsiteIds([$storeManager->getStore()->getWebsiteId()]);

        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);

        return $productRepository->save($product);
    }

    /**
     * Creates an IndexingEntity row for the given product in the specified
     * initial state so the observer has a record to act upon.
     */
    private function createIndexingEntityForProduct(
        int    $productId,
        string $lastAction,
        bool   $isIndexable
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

    /**
     * Fetches the first IndexingEntity matching the given target_id.
     */
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
