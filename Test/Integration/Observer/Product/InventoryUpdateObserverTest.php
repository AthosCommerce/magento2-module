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
use Magento\CatalogInventory\Model\Stock\Item as StockItem;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for cataloginventory_stock_item_save_after → InventoryUpdateObserver flow.
 *
 * Covers:
 *  - next_action and is_indexable for every relevant visibility/status combination
 *    when qty or is_in_stock has changed.
 *  - Early-exit guard: observer must be a no-op when neither qty nor is_in_stock changed.
 *  - Live-indexing disabled: observer must skip all processing.
 *
 * Key characteristics of InventoryUpdateObserver vs UpdateObserver:
 *  - Event carries a StockItem; the product is loaded separately by the observer.
 *  - forceIndexable is NOT passed → defaults to false (same behaviour as DeleteObserver).
 *  - Observer exits early when dataHasChangedFor('qty') and dataHasChangedFor('is_in_stock')
 *    are both false.
 *
 * @magentoDbIsolation enabled
 * @covers \AthosCommerce\Feed\Observer\Product\InventoryUpdateObserver
 * @covers \AthosCommerce\Feed\Observer\BaseProductObserver
 * @covers \AthosCommerce\Feed\Service\Action\SetIndexingEntitiesToUpdateAction
 * @covers \AthosCommerce\Feed\Service\Action\SetIndexingEntitiesToDeleteAction
 */
class InventoryUpdateObserverTest extends TestCase
{
    use IndexingEntitiesTrait;

    private const SITE_ID_PREFIX = 'test-inv-observer-';

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
    // Happy-path: qty changed
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * When qty has changed the observer must update next_action and is_indexable
     * according to the product's current status and visibility.
     *
     * @dataProvider stockChangeDataProvider
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_WhenQtyChanged_UpdatesNextActionAndIsIndexable(
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

        $stockItem = $this->buildStockItem(
            $productId,
            currentQty: 20,
            origQty: 10,        // qty changed (20 ≠ 10)
            currentInStock: 1,
            origInStock: 1      // is_in_stock unchanged
        );

        $this->dispatchStockItemSaveAfter($stockItem);

        $entity = $this->getIndexingEntityByTargetId($productId);

        $this->assertNotNull($entity, sprintf('No IndexingEntity for product id=%d', $productId));
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

    // ──────────────────────────────────────────────────────────────────────────
    // Happy-path: is_in_stock changed
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * When is_in_stock has changed (product goes out-of-stock / back in-stock)
     * the observer must update the indexing entity.
     *
     * @dataProvider stockChangeDataProvider
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_WhenIsInStockChanged_UpdatesNextActionAndIsIndexable(
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

        $stockItem = $this->buildStockItem(
            $productId,
            currentQty: 10,
            origQty: 10,        // qty unchanged
            currentInStock: 0,
            origInStock: 1      // is_in_stock changed (1 → 0)
        );

        $this->dispatchStockItemSaveAfter($stockItem);

        $entity = $this->getIndexingEntityByTargetId($productId);

        $this->assertNotNull($entity, sprintf('No IndexingEntity for product id=%d', $productId));
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

    // ──────────────────────────────────────────────────────────────────────────
    // Early-exit: no stock change
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * When neither qty nor is_in_stock has changed the observer must exit early
     * and leave the indexing entity in its original state.
     *
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_WhenNeitherQtyNorStockChanged_LeavesIndexingEntityUnchanged(): void
    {
        $product = $this->createAndSaveTestProduct(Status::STATUS_ENABLED, Visibility::VISIBILITY_BOTH);
        $productId = (int)$product->getId();

        $this->createIndexingEntityForProduct($productId, Actions::NO_ACTION, false);

        $stockItem = $this->buildStockItem(
            $productId,
            currentQty: 10,
            origQty: 10,    // qty unchanged
            currentInStock: 1,
            origInStock: 1  // is_in_stock unchanged
        );

        $this->dispatchStockItemSaveAfter($stockItem);

        $entity = $this->getIndexingEntityByTargetId($productId);

        $this->assertNotNull($entity);
        $this->assertSame(
            Actions::NO_ACTION,
            $entity->getNextAction(),
            'next_action must remain NO_ACTION when no stock fields changed'
        );
        $this->assertFalse(
            $entity->getIsIndexable(),
            'is_indexable must remain false when no stock fields changed'
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Live indexing disabled
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * When live indexing is disabled the observer must skip all stores and
     * leave the indexing entity unchanged, even when stock has changed.
     *
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 0
     */
    public function testExecute_WhenLiveIndexingDisabled_LeavesIndexingEntityUnchanged(): void
    {
        $product = $this->createAndSaveTestProduct(Status::STATUS_ENABLED, Visibility::VISIBILITY_BOTH);
        $productId = (int)$product->getId();

        $this->createIndexingEntityForProduct($productId, Actions::UPSERT, true);

        $stockItem = $this->buildStockItem(
            $productId,
            currentQty: 20,
            origQty: 10,    // qty changed – would normally trigger processing
            currentInStock: 1,
            origInStock: 1
        );

        $this->dispatchStockItemSaveAfter($stockItem);

        $entity = $this->getIndexingEntityByTargetId($productId);

        $this->assertNotNull($entity);
        $this->assertSame(
            Actions::NO_ACTION,
            $entity->getNextAction(),
            'next_action must be NO_ACTION when live indexing is disabled'
        );
        $this->assertTrue(
            $entity->getIsIndexable(),
            'is_indexable must remain true when live indexing is disabled'
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Data provider
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Covers all relevant visibility/status combinations for stock-change scenarios.
     *
     * Observer logic (InventoryUpdateObserver):
     *   nextAction = (status != ENABLED || visibility == NOT_VISIBLE) ? DELETE : UPSERT
     *   forceIndexable = false  (not passed → default)
     *
     * SetIndexingEntitiesToUpdateAction (forceIndexable=false):
     *   → is_indexable=true  : next_action = UPSERT            (entity updated)
     *   → is_indexable=false : entity SKIPPED (no DB change)
     *
     * SetIndexingEntitiesToDeleteAction:
     *   → last_action == NO_ACTION : next_action = NO_ACTION, is_indexable = false
     *   → last_action != NO_ACTION : next_action = DELETE       (is_indexable unchanged)
     */
    public function stockChangeDataProvider(): array
    {
        return [
            // ── UPSERT path (enabled + visible) ───────────────────────────────
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
            // forceIndexable=false: non-indexable entity is skipped on UPSERT path
            'enabled_visibility_both_not_indexable_skipped' => [
                'status'              => Status::STATUS_ENABLED,
                'visibility'          => Visibility::VISIBILITY_BOTH,
                'initialLastAction'   => Actions::NO_ACTION,
                'initialIsIndexable'  => false,
                'expectedNextAction'  => Actions::NO_ACTION,   // unchanged – skipped
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

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Creates and saves a simple product with the given status and visibility.
     * The observer must not act on the resulting save event because no
     * IndexingEntity exists at that point.
     */
    private function createAndSaveTestProduct(int $status, int $visibility): Product
    {
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);

        /** @var Product $product */
        $product = $this->objectManager->create(Product::class);
        $product->setTypeId('simple')
            ->setAttributeSetId(4)
            ->setName('InventoryObserver Test ' . uniqid('', true))
            ->setSku('athos_inv_obs_' . uniqid('', true))
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
     * Builds a StockItem data object with explicit current and original values
     * so that dataHasChangedFor() returns the desired result.
     *
     * dataHasChangedFor($key) compares getData($key) with getOrigData($key).
     */
    private function buildStockItem(
        int $productId,
        int $currentQty,
        int $origQty,
        int $currentInStock,
        int $origInStock
    ): StockItem {
        /** @var StockItem $stockItem */
        $stockItem = $this->objectManager->create(StockItem::class);
        $stockItem->setProductId($productId);
        $stockItem->setData('qty', $currentQty);
        $stockItem->setData('is_in_stock', $currentInStock);
        // Set original data so dataHasChangedFor() can detect the diff.
        $stockItem->setOrigData('qty', $origQty);
        $stockItem->setOrigData('is_in_stock', $origInStock);

        return $stockItem;
    }

    /**
     * Dispatches cataloginventory_stock_item_save_after with the given stock item,
     * mirroring what Magento fires after persisting the stock row.
     */
    private function dispatchStockItemSaveAfter(StockItem $stockItem): void
    {
        /** @var EventManagerInterface $eventManager */
        $eventManager = $this->objectManager->get(EventManagerInterface::class);
        $eventManager->dispatch(
            'cataloginventory_stock_item_save_after',
            ['item' => $stockItem]
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
