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
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for catalog_product_save_after → UpdateObserver flow.
 *
 * Verifies that saving a product correctly updates next_action and is_indexable
 * on the corresponding athoscommerce_indexing_entity row, covering all
 * visibility/status combinations.
 *
 * @magentoDbIsolation enabled
 * @covers \AthosCommerce\Feed\Observer\Product\UpdateObserver
 * @covers \AthosCommerce\Feed\Observer\BaseProductObserver
 * @covers \AthosCommerce\Feed\Service\Action\SetIndexingEntitiesToUpdateAction
 * @covers \AthosCommerce\Feed\Service\Action\SetIndexingEntitiesToDeleteAction
 */
class UpdateObserverTest extends TestCase
{
    use IndexingEntitiesTrait;

    private const SITE_ID_PREFIX = 'test-update-observer-';

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
     * Tests that saving a product updates next_action and is_indexable on the
     * indexing entity according to the product's status and visibility.
     *
     * UpdateObserver calls BaseProductObserver with forceIndexable=true, so
     * is_indexable is always forced to true on UPSERT regardless of initial state.
     *
     * @dataProvider productSaveDataProvider
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_OnProductSave_UpdatesNextActionAndIsIndexable(
        int    $status,
        int    $visibility,
        string $initialLastAction,
        bool   $initialIsIndexable,
        string $expectedNextAction,
        bool   $expectedIsIndexable
    ): void {
        // Step 1: Create the product. The save triggers the event, but no
        // IndexingEntity exists yet so the observer has nothing to update.
        $product = $this->createAndSaveTestProduct($status, $visibility);
        $productId = (int)$product->getId();

        // Step 2: Create the IndexingEntity in its initial state.
        $this->createIndexingEntityForProduct($productId, $initialLastAction, $initialIsIndexable);

        // Step 3: Re-save the product to trigger catalog_product_save_after again.
        // Now the IndexingEntity exists and the observer will update it.
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $productRepository->save($product);

        // Step 4: Reload and assert the indexing entity was updated.
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
     * Tests that when live indexing is disabled the observer skips processing
     * and the indexing entity remains in its initial state.
     *
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 0
     */
    public function testExecute_WhenLiveIndexingDisabled_LeavesIndexingEntityUnchanged(): void
    {
        $product = $this->createAndSaveTestProduct(Status::STATUS_ENABLED, Visibility::VISIBILITY_BOTH);
        $productId = (int)$product->getId();

        $this->createIndexingEntityForProduct($productId, Actions::NO_ACTION, false);

        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $productRepository->save($product);

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

    /**
     * Data provider covering all relevant status / visibility combinations.
     *
     * Observer logic (UpdateObserver):
     *   nextAction = (status != ENABLED || visibility == NOT_VISIBLE) ? DELETE : UPSERT
     *   forceIndexable = true (always passed by UpdateObserver)
     *
     * SetIndexingEntitiesToUpdateAction (forceIndexable=true):
     *   → next_action = UPSERT, is_indexable = true  (unconditionally)
     *
     * SetIndexingEntitiesToDeleteAction:
     *   → last_action == NO_ACTION : next_action = NO_ACTION, is_indexable = false
     *   → last_action != NO_ACTION : next_action = DELETE       (is_indexable unchanged)
     */
    public function productSaveDataProvider(): array
    {
        return [
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
            'enabled_visibility_both_already_indexable_expects_upsert' => [
                'status'              => Status::STATUS_ENABLED,
                'visibility'          => Visibility::VISIBILITY_BOTH,
                'initialLastAction'   => Actions::UPSERT,
                'initialIsIndexable'  => true,
                'expectedNextAction'  => Actions::UPSERT,
                'expectedIsIndexable' => true,
            ],
            'disabled_visibility_both_last_action_upsert_expects_delete' => [
                'status'              => Status::STATUS_DISABLED,
                'visibility'          => Visibility::VISIBILITY_BOTH,
                'initialLastAction'   => Actions::UPSERT,
                'initialIsIndexable'  => true,
                'expectedNextAction'  => Actions::DELETE,
                'expectedIsIndexable' => true,   // is_indexable unchanged by delete action
            ],
            'disabled_visibility_both_last_action_no_action_expects_no_action' => [
                'status'              => Status::STATUS_DISABLED,
                'visibility'          => Visibility::VISIBILITY_BOTH,
                'initialLastAction'   => Actions::NO_ACTION,
                'initialIsIndexable'  => true,
                'expectedNextAction'  => Actions::NO_ACTION,
                'expectedIsIndexable' => false,
            ],
            'enabled_not_visible_last_action_upsert_expects_delete' => [
                'status'              => Status::STATUS_ENABLED,
                'visibility'          => Visibility::VISIBILITY_NOT_VISIBLE,
                'initialLastAction'   => Actions::UPSERT,
                'initialIsIndexable'  => true,
                'expectedNextAction'  => Actions::DELETE,
                'expectedIsIndexable' => true,   // is_indexable unchanged by delete action
            ],
            'enabled_not_visible_last_action_no_action_expects_no_action' => [
                'status'              => Status::STATUS_ENABLED,
                'visibility'          => Visibility::VISIBILITY_NOT_VISIBLE,
                'initialLastAction'   => Actions::NO_ACTION,
                'initialIsIndexable'  => true,
                'expectedNextAction'  => Actions::NO_ACTION,
                'expectedIsIndexable' => false,
            ],
            'disabled_not_visible_last_action_upsert_expects_delete' => [
                'status'              => Status::STATUS_DISABLED,
                'visibility'          => Visibility::VISIBILITY_NOT_VISIBLE,
                'initialLastAction'   => Actions::UPSERT,
                'initialIsIndexable'  => true,
                'expectedNextAction'  => Actions::DELETE,
                'expectedIsIndexable' => true,
            ],
            'disabled_not_visible_last_action_no_action_expects_no_action' => [
                'status'              => Status::STATUS_DISABLED,
                'visibility'          => Visibility::VISIBILITY_NOT_VISIBLE,
                'initialLastAction'   => Actions::NO_ACTION,
                'initialIsIndexable'  => false,
                'expectedNextAction'  => Actions::NO_ACTION,
                'expectedIsIndexable' => false,
            ],
        ];
    }

    /**
     * Creates and persists a simple product with the given status and visibility.
     * The first save triggers catalog_product_save_after but no IndexingEntity
     * exists at that point, so the observer is a no-op.
     */
    private function createAndSaveTestProduct(int $status, int $visibility): Product
    {
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);

        /** @var Product $product */
        $product = $this->objectManager->create(Product::class);
        $product->setTypeId('simple')
            ->setAttributeSetId(4)
            ->setName('UpdateObserver Test ' . uniqid('', true))
            ->setSku('athos_upd_obs_' . uniqid('', true))
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
     * initial state so the observer has a record to update.
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
