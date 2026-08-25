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
 * Integration test for catalog_product_import_bunch_save_after → BunchSaveObserver flow.
 *
 * BunchSaveObserver differs from the other product observers in two ways:
 *
 *  1. It receives an import BUNCH (array of rows each containing a 'sku' key) instead
 *     of a single product model. Entity IDs are resolved via a raw SQL lookup, so real
 *     products must exist in the database.
 *  2. It processes multiple products in a single invocation.
 *
 * Like all other observers it now respects status/visibility:
 *  - enabled + visible  → UPSERT  (forceIndexable=true)
 *  - disabled or not-visible → DELETE (forceIndexable=false, same as DeleteObserver)
 *
 * Guard conditions tested:
 *  - Empty bunch array            → early exit, no change.
 *  - Bunch rows without 'sku' key → early exit, no change.
 *  - SKUs absent from catalog_product_entity → early exit, no change.
 *  - Live indexing disabled       → per-store skip, no change.
 *
 * @magentoDbIsolation enabled
 * @covers \AthosCommerce\Feed\Observer\Product\BunchSaveObserver
 * @covers \AthosCommerce\Feed\Observer\BaseProductObserver
 * @covers \AthosCommerce\Feed\Service\Action\SetIndexingEntitiesToUpdateAction
 * @covers \AthosCommerce\Feed\Service\Action\SetIndexingEntitiesToDeleteAction
 */
class BunchSaveObserverTest extends TestCase
{
    use IndexingEntitiesTrait;

    private const SITE_ID_PREFIX = 'test-bunch-observer-';

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

    // ──────────────────────────────────────────────────────────────────────────
    // Happy-path: visibility / status routing
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * A bunch containing one SKU must route the product to UPSERT or DELETE
     * based on its status and visibility, matching the behaviour of the other
     * product observers.
     *
     * @dataProvider bunchSaveDataProvider
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_WithSingleProductBunch_RespectsStatusAndVisibility(
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

        $this->dispatchBunchSaveAfter([['sku' => $product->getSku()]]);

        $entity = $this->getIndexingEntityByTargetId($productId);

        $this->assertNotNull($entity, sprintf('No IndexingEntity found for product id=%d', $productId));
        $this->assertSame(
            $expectedNextAction,
            $entity->getNextAction(),
            sprintf('next_action mismatch for status=%d visibility=%d', $status, $visibility)
        );
        $this->assertSame(
            $expectedIsIndexable,
            $entity->getIsIndexable(),
            sprintf('is_indexable mismatch for status=%d visibility=%d', $status, $visibility)
        );
    }

    /**
     * Covers all relevant visibility/status combinations.
     *
     * Observer logic (BunchSaveObserver after fix):
     *   nextAction = (status != ENABLED || visibility == NOT_VISIBLE) ? DELETE : UPSERT
     *
     * SetIndexingEntitiesToUpdateAction (forceIndexable=true, UPSERT path):
     *   → next_action = UPSERT, is_indexable = true  (unconditionally)
     *
     * SetIndexingEntitiesToDeleteAction (DELETE path):
     *   → last_action == NO_ACTION : next_action = NO_ACTION, is_indexable = false
     *   → last_action != NO_ACTION : next_action = DELETE       (is_indexable unchanged)
     */
    public function bunchSaveDataProvider(): array
    {
        return [
            // ── UPSERT path (enabled + visible) ───────────────────────────────
            'enabled_visibility_both_expects_upsert' => [
                'status'              => Status::STATUS_ENABLED,
                'visibility'          => Visibility::VISIBILITY_BOTH,
                'initialLastAction'   => Actions::NO_ACTION,
                'initialIsIndexable'  => false,
                'expectedNextAction'  => Actions::UPSERT,
                'expectedIsIndexable' => true,
            ],
            'enabled_visibility_in_catalog_expects_upsert' => [
                'status'              => Status::STATUS_ENABLED,
                'visibility'          => Visibility::VISIBILITY_IN_CATALOG,
                'initialLastAction'   => Actions::NO_ACTION,
                'initialIsIndexable'  => false,
                'expectedNextAction'  => Actions::UPSERT,
                'expectedIsIndexable' => true,
            ],
            'enabled_visibility_in_search_expects_upsert' => [
                'status'              => Status::STATUS_ENABLED,
                'visibility'          => Visibility::VISIBILITY_IN_SEARCH,
                'initialLastAction'   => Actions::NO_ACTION,
                'initialIsIndexable'  => false,
                'expectedNextAction'  => Actions::UPSERT,
                'expectedIsIndexable' => true,
            ],
            // forceIndexable=true overrides a previously non-indexable entity
            'enabled_visibility_both_not_indexable_becomes_indexable' => [
                'status'              => Status::STATUS_ENABLED,
                'visibility'          => Visibility::VISIBILITY_BOTH,
                'initialLastAction'   => Actions::NO_ACTION,
                'initialIsIndexable'  => false,
                'expectedNextAction'  => Actions::UPSERT,
                'expectedIsIndexable' => true,
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
                'initialIsIndexable'  => true,
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

    // ──────────────────────────────────────────────────────────────────────────
    // Happy-path: multiple products in one bunch
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * A bunch containing several SKUs must route each product to UPSERT or DELETE
     * independently based on its own status and visibility.
     *
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_WithMultipleProductsBunch_RoutesEachProductCorrectly(): void
    {
        // enabled + visible  → UPSERT
        $productA = $this->createAndSaveTestProduct(Status::STATUS_ENABLED, Visibility::VISIBILITY_BOTH);
        // disabled           → DELETE
        $productB = $this->createAndSaveTestProduct(Status::STATUS_DISABLED, Visibility::VISIBILITY_BOTH);
        // not-visible        → DELETE
        $productC = $this->createAndSaveTestProduct(Status::STATUS_ENABLED, Visibility::VISIBILITY_NOT_VISIBLE);

        $idA = (int)$productA->getId();
        $idB = (int)$productB->getId();
        $idC = (int)$productC->getId();

        $this->createIndexingEntityForProduct($idA, Actions::NO_ACTION, false);
        $this->createIndexingEntityForProduct($idB, Actions::UPSERT, true);
        $this->createIndexingEntityForProduct($idC, Actions::UPSERT, true);

        $this->dispatchBunchSaveAfter([
            ['sku' => $productA->getSku()],
            ['sku' => $productB->getSku()],
            ['sku' => $productC->getSku()],
        ]);

        $entityA = $this->getIndexingEntityByTargetId($idA);
        $this->assertSame(Actions::UPSERT, $entityA->getNextAction(), 'Product A must be UPSERT');
        $this->assertTrue($entityA->getIsIndexable(), 'Product A must be indexable');

        $entityB = $this->getIndexingEntityByTargetId($idB);
        $this->assertSame(Actions::DELETE, $entityB->getNextAction(), 'Product B (disabled) must be DELETE');

        $entityC = $this->getIndexingEntityByTargetId($idC);
        $this->assertSame(Actions::DELETE, $entityC->getNextAction(), 'Product C (not-visible) must be DELETE');
    }

    /**
     * A bunch that mixes existing and non-existing SKUs must only update entities
     * for the products that are actually present in the database.
     *
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_WithMixedExistingAndMissingSku_OnlyProcessesExistingProducts(): void
    {
        $product = $this->createAndSaveTestProduct(Status::STATUS_ENABLED, Visibility::VISIBILITY_BOTH);
        $productId = (int)$product->getId();

        $this->createIndexingEntityForProduct($productId, Actions::NO_ACTION, false);

        $this->dispatchBunchSaveAfter([
            ['sku' => $product->getSku()],
            ['sku' => 'athos-nonexistent-sku-' . uniqid('', true)],
        ]);

        $entity = $this->getIndexingEntityByTargetId($productId);

        $this->assertNotNull($entity);
        $this->assertSame(Actions::UPSERT, $entity->getNextAction());
        $this->assertTrue($entity->getIsIndexable());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Guard: empty bunch
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * An empty bunch must trigger the early-exit guard and leave the
     * IndexingEntity untouched.
     *
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_WithEmptyBunch_LeavesIndexingEntityUnchanged(): void
    {
        $product = $this->createAndSaveTestProduct();
        $productId = (int)$product->getId();

        $this->createIndexingEntityForProduct($productId, Actions::NO_ACTION, false);

        $this->dispatchBunchSaveAfter([]);

        $entity = $this->getIndexingEntityByTargetId($productId);

        $this->assertNotNull($entity);
        $this->assertSame(Actions::NO_ACTION, $entity->getNextAction(), 'next_action must be unchanged');
        $this->assertFalse($entity->getIsIndexable(), 'is_indexable must be unchanged');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Guard: bunch rows without 'sku' key
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * When bunch rows contain no 'sku' key array_column returns an empty array,
     * which triggers the early-exit guard. Nothing must be updated.
     *
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_WhenBunchRowsHaveNoSkuKey_LeavesIndexingEntityUnchanged(): void
    {
        $product = $this->createAndSaveTestProduct();
        $productId = (int)$product->getId();

        $this->createIndexingEntityForProduct($productId, Actions::NO_ACTION, false);

        // Rows with no 'sku' field — array_column(..., 'sku') returns [].
        $this->dispatchBunchSaveAfter([
            ['name' => 'Import Row Without SKU', 'price' => 9.99],
        ]);

        $entity = $this->getIndexingEntityByTargetId($productId);

        $this->assertNotNull($entity);
        $this->assertSame(Actions::NO_ACTION, $entity->getNextAction(), 'next_action must be unchanged');
        $this->assertFalse($entity->getIsIndexable(), 'is_indexable must be unchanged');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Guard: SKUs absent from catalog_product_entity
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * When no row in catalog_product_entity matches any SKU in the bunch the
     * fetchAll returns an empty array and the observer exits early.
     *
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_WhenSkusNotInDatabase_LeavesIndexingEntityUnchanged(): void
    {
        $product = $this->createAndSaveTestProduct();
        $productId = (int)$product->getId();

        $this->createIndexingEntityForProduct($productId, Actions::UPSERT, true);

        $this->dispatchBunchSaveAfter([
            ['sku' => 'athos-definitely-not-existing-' . uniqid('', true)],
        ]);

        $entity = $this->getIndexingEntityByTargetId($productId);

        $this->assertNotNull($entity);
        // next_action was initialised to NO_ACTION by createIndexingEntityForProduct
        $this->assertSame(Actions::NO_ACTION, $entity->getNextAction(), 'next_action must be unchanged');
        $this->assertTrue($entity->getIsIndexable(), 'is_indexable must be unchanged');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Guard: live indexing disabled
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * When live indexing is disabled for every store the product belongs to,
     * shouldProcess stays false and the entity must remain untouched.
     *
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 0
     */
    public function testExecute_WhenLiveIndexingDisabled_LeavesIndexingEntityUnchanged(): void
    {
        $product = $this->createAndSaveTestProduct();
        $productId = (int)$product->getId();

        $this->createIndexingEntityForProduct($productId, Actions::NO_ACTION, false);

        $this->dispatchBunchSaveAfter([['sku' => $product->getSku()]]);

        $entity = $this->getIndexingEntityByTargetId($productId);

        $this->assertNotNull($entity);
        $this->assertSame(
            Actions::NO_ACTION,
            $entity->getNextAction(),
            'next_action must be unchanged when live indexing is disabled'
        );
        $this->assertFalse(
            $entity->getIsIndexable(),
            'is_indexable must be unchanged when live indexing is disabled'
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Creates and saves a simple product. Status and visibility are set to the
     * most common import scenario (enabled, visible both) — the observer does not
     * check them, so they do not affect the expected outcome.
     */
    private function createAndSaveTestProduct(
        int $status = Status::STATUS_ENABLED,
        int $visibility = Visibility::VISIBILITY_BOTH
    ): Product {
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);

        /** @var Product $product */
        $product = $this->objectManager->create(Product::class);
        $product->setTypeId('simple')
            ->setAttributeSetId(4)
            ->setName('BunchObserver Test ' . uniqid('', true))
            ->setSku('athos_bunch_obs_' . uniqid('', true))
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
     * Dispatches catalog_product_import_bunch_save_after with the given rows,
     * mirroring what Magento fires at the end of each import bunch persist.
     *
     * @param array<int, array<string, mixed>> $bunch
     */
    private function dispatchBunchSaveAfter(array $bunch): void
    {
        /** @var EventManagerInterface $eventManager */
        $eventManager = $this->objectManager->get(EventManagerInterface::class);
        $eventManager->dispatch(
            'catalog_product_import_bunch_save_after',
            ['bunch' => $bunch]
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
