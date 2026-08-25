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
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for catalog_product_import_bunch_delete_after → BunchDeleteObserver flow.
 *
 * BunchDeleteObserver is the simplest of all product observers:
 *  - Event carries a plain array of product IDs (ids_to_delete), not SKUs or product models.
 *  - Live-indexing check: returns early if no active store has live indexing enabled.
 *    Since deleted products can no longer be queried for their store associations, the
 *    observer checks whether ANY active store has live indexing on (consistent with the
 *    per-store SCOPE_STORE approach used in all other product observers).
 *  - No status/visibility check — always calls DELETE.
 *  - Deduplicates IDs with array_unique() before processing.
 *  - forceIndexable = false (default), so SetIndexingEntitiesToDeleteAction rules apply:
 *      last_action == NO_ACTION → next_action = NO_ACTION, is_indexable = false
 *      last_action != NO_ACTION → next_action = DELETE     (is_indexable unchanged)
 *
 * Because the observer does not load or inspect product records, tests create
 * IndexingEntity rows directly without needing real catalog products.
 *
 * @magentoDbIsolation enabled
 * @covers \AthosCommerce\Feed\Observer\Product\BunchDeleteObserver
 * @covers \AthosCommerce\Feed\Observer\BaseProductObserver
 * @covers \AthosCommerce\Feed\Service\Action\SetIndexingEntitiesToDeleteAction
 */
class BunchDeleteObserverTest extends TestCase
{
    use IndexingEntitiesTrait;

    private const SITE_ID_PREFIX = 'test-bunch-delete-observer-';

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
     * Dispatching the event with a product ID must update the corresponding
     * IndexingEntity according to SetIndexingEntitiesToDeleteAction rules.
     *
     * @dataProvider deleteActionDataProvider
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_WithProductId_SetsCorrectNextActionAndIsIndexable(
        string $initialLastAction,
        bool   $initialIsIndexable,
        string $expectedNextAction,
        bool   $expectedIsIndexable
    ): void {
        $productId = $this->uniqueProductId();
        $this->createIndexingEntityForProduct($productId, $initialLastAction, $initialIsIndexable);

        $this->dispatchBunchDeleteAfter([$productId]);

        $entity = $this->getIndexingEntityByTargetId($productId);

        $this->assertNotNull($entity, "No IndexingEntity found for product id={$productId}");
        $this->assertSame(
            $expectedNextAction,
            $entity->getNextAction(),
            "next_action mismatch (initialLastAction={$initialLastAction})"
        );
        $this->assertSame(
            $expectedIsIndexable,
            $entity->getIsIndexable(),
            "is_indexable mismatch (initialLastAction={$initialLastAction})"
        );
    }

    /**
     * SetIndexingEntitiesToDeleteAction behaviour (forceIndexable=false):
     *   last_action == NO_ACTION → next_action = NO_ACTION, is_indexable = false
     *   last_action != NO_ACTION → next_action = DELETE     (is_indexable unchanged)
     */
    public function deleteActionDataProvider(): array
    {
        return [
            'last_action_upsert_expects_delete' => [
                'initialLastAction'   => Actions::UPSERT,
                'initialIsIndexable'  => true,
                'expectedNextAction'  => Actions::DELETE,
                'expectedIsIndexable' => true,
            ],
            'last_action_delete_expects_delete' => [
                'initialLastAction'   => Actions::DELETE,
                'initialIsIndexable'  => true,
                'expectedNextAction'  => Actions::DELETE,
                'expectedIsIndexable' => true,
            ],
            'last_action_no_action_expects_no_action_and_not_indexable' => [
                'initialLastAction'   => Actions::NO_ACTION,
                'initialIsIndexable'  => true,
                'expectedNextAction'  => Actions::NO_ACTION,
                'expectedIsIndexable' => false,
            ],
        ];
    }

    /**
     * All product IDs in the event must have their IndexingEntity updated
     * in a single observer call.
     *
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_WithMultipleProductIds_UpdatesAllEntities(): void
    {
        $idA = $this->uniqueProductId();
        $idB = $this->uniqueProductId();
        $idC = $this->uniqueProductId();

        $this->createIndexingEntityForProduct($idA, Actions::UPSERT, true);
        $this->createIndexingEntityForProduct($idB, Actions::UPSERT, true);
        $this->createIndexingEntityForProduct($idC, Actions::NO_ACTION, true);

        $this->dispatchBunchDeleteAfter([$idA, $idB, $idC]);

        $entityA = $this->getIndexingEntityByTargetId($idA);
        $entityB = $this->getIndexingEntityByTargetId($idB);
        $entityC = $this->getIndexingEntityByTargetId($idC);

        $this->assertSame(Actions::DELETE, $entityA->getNextAction(), 'Entity A must be DELETE');
        $this->assertSame(Actions::DELETE, $entityB->getNextAction(), 'Entity B must be DELETE');
        $this->assertSame(Actions::NO_ACTION, $entityC->getNextAction(), 'Entity C (last=NO_ACTION) must be NO_ACTION');
        $this->assertFalse($entityC->getIsIndexable(), 'Entity C must not be indexable');
    }

    // Deduplication

    /**
     * Duplicate product IDs must be deduplicated (array_unique) before processing.
     * The entity must end up in the same state as if the ID appeared only once.
     *
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_WithDuplicateProductIds_DeduplicatesAndUpdatesCorrectly(): void
    {
        $productId = $this->uniqueProductId();
        $this->createIndexingEntityForProduct($productId, Actions::UPSERT, true);

        $this->dispatchBunchDeleteAfter([$productId, $productId, $productId]);

        $entity = $this->getIndexingEntityByTargetId($productId);

        $this->assertNotNull($entity);
        $this->assertSame(
            Actions::DELETE,
            $entity->getNextAction(),
            'next_action must be DELETE even when ID was duplicated in the event'
        );
    }

    // Guard: empty IDs

    /**
     * An empty ids_to_delete array must trigger the early-exit guard and leave
     * all IndexingEntities untouched.
     *
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_WithEmptyProductIds_LeavesIndexingEntityUnchanged(): void
    {
        $productId = $this->uniqueProductId();
        $this->createIndexingEntityForProduct($productId, Actions::UPSERT, true);

        $this->dispatchBunchDeleteAfter([]);

        $entity = $this->getIndexingEntityByTargetId($productId);

        $this->assertNotNull($entity);
        $this->assertSame(
            Actions::NO_ACTION,
            $entity->getNextAction(),
            'next_action must remain NO_ACTION when ids_to_delete is empty'
        );
        $this->assertTrue($entity->getIsIndexable(), 'is_indexable must remain unchanged');
    }

    // Guard: live indexing disabled for all stores

    /**
     * When live indexing is disabled for every active store the observer must
     * skip all processing and leave the IndexingEntity in its initial state.
     *
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 0
     */
    public function testExecute_WhenLiveIndexingDisabled_LeavesIndexingEntityUnchanged(): void
    {
        $productId = $this->uniqueProductId();
        $this->createIndexingEntityForProduct($productId, Actions::UPSERT, true);

        $this->dispatchBunchDeleteAfter([$productId]);

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
     * When the current store is disabled but another active store has live indexing
     * enabled, the observer must still process deletions.
     *
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 0
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/config.php
     */
    public function testExecute_WhenAnotherStoreHasLiveIndexingEnabled_ProcessesDeletion(): void
    {
        $productId = $this->uniqueProductId();
        $this->createIndexingEntityForProduct($productId, Actions::UPSERT, true);

        $this->dispatchBunchDeleteAfter([$productId]);

        $entity = $this->getIndexingEntityByTargetId($productId);

        $this->assertNotNull($entity);
        $this->assertSame(
            Actions::DELETE,
            $entity->getNextAction(),
            'next_action must become DELETE when any active store has live indexing enabled'
        );
        $this->assertTrue(
            $entity->getIsIndexable(),
            'is_indexable must remain unchanged on delete path'
        );
    }

    // Helpers

    /**
     * Returns a product ID that is extremely unlikely to exist in the test DB.
     * BunchDeleteObserver never loads the product — it only passes IDs to the
     * action service — so a synthetic ID is sufficient.
     */
    private function uniqueProductId(): int
    {
        return random_int(900000000, 999999999);
    }

    /**
     * Creates an IndexingEntity row in the specified initial state.
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
     * Dispatches catalog_product_import_bunch_delete_after with the given IDs,
     * mirroring what Magento fires after an import bunch deletion.
     *
     * getIdsToDelete() on the Event object maps to the 'ids_to_delete' key.
     *
     * @param int[] $productIds
     */
    private function dispatchBunchDeleteAfter(array $productIds): void
    {
        /** @var EventManagerInterface $eventManager */
        $eventManager = $this->objectManager->get(EventManagerInterface::class);
        $eventManager->dispatch(
            'catalog_product_import_bunch_delete_after',
            ['ids_to_delete' => $productIds]
        );
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
