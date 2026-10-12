<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Integration\Service\Action;

use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Model\ResourceModel\IndexingEntity as IndexingEntityResourceModel;
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Service\Action\SyncSiteAssignmentAction;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Sibling re-queue rules: a changed variant re-queues Upsert only for sibling variants that are
 * live in Athos and idle (indexable, last action Upsert, no pending action).
 *
 * Target ids are synthetic: the parent is found from the changed variant's own row.
 *
 * @magentoDbIsolation enabled
 * @covers \AthosCommerce\Feed\Service\Action\SyncSiteAssignmentAction::queueSiblingUpserts
 * @covers \AthosCommerce\Feed\Service\Action\SyncSiteAssignmentAction::getLinkedChildIds
 * @covers \AthosCommerce\Feed\Service\Action\SyncSiteAssignmentAction::queueDeleteForUnassignedSites
 * @covers \AthosCommerce\Feed\Plugin\Catalog\ProductActionPlugin::afterUpdateWebsites
 */
class SyncSiteAssignmentActionTest extends TestCase
{
    private const PARENT_ID = 990000001;
    private const CHANGED_ID = 990000002;
    private const SIBLING_ID = 990000003;
    private const SITE = 'test-siblings-site';
    private const OTHER_SITE = 'test-siblings-other';
    private const SITE_A = 'test-sib-site-a';
    private const SITE_B = 'test-sib-site-b';
    private const SECOND_STORE = 'fixture_second_store';
    private const OLD_PARENT_ID = 990000099;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var SyncSiteAssignmentAction
     */
    private $action;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->action = $this->objectManager->get(SyncSiteAssignmentAction::class);
    }

    /**
     * An idle sibling that is live in Athos is re-queued; the changed variant itself is not touched.
     */
    public function testLiveIdleSiblingIsRequeued(): void
    {
        $changed = $this->createRow(self::CHANGED_ID, Actions::UPSERT, Actions::NO_ACTION, true);
        $sibling = $this->createRow(self::SIBLING_ID, Actions::UPSERT, Actions::NO_ACTION, true);

        $count = $this->action->queueSiblingUpserts([self::CHANGED_ID], [self::SITE]);

        $this->assertSame(1, $count);
        $this->assertSame(Actions::UPSERT, $this->reload($sibling)->getNextAction());
        $this->assertTrue($this->reload($sibling)->getIsIndexable());
        $this->assertSame(Actions::NO_ACTION, $this->reload($changed)->getNextAction());
    }

    /**
     * A sibling waiting for its Delete keeps it: re-queueing Upsert would resurrect it.
     */
    public function testSiblingPendingDeleteIsLeftAlone(): void
    {
        $this->createRow(self::CHANGED_ID, Actions::UPSERT, Actions::NO_ACTION, true);
        $sibling = $this->createRow(self::SIBLING_ID, Actions::UPSERT, Actions::DELETE, true);

        $this->assertSame(0, $this->action->queueSiblingUpserts([self::CHANGED_ID], [self::SITE]));
        $this->assertSame(Actions::DELETE, $this->reload($sibling)->getNextAction());
    }

    /**
     * Siblings that were never sent, or were deleted from Athos, are not pushed by this path.
     */
    public function testSiblingNotLiveInAthosIsLeftAlone(): void
    {
        $this->createRow(self::CHANGED_ID, Actions::UPSERT, Actions::NO_ACTION, true);
        $neverSent = $this->createRow(self::SIBLING_ID, Actions::NO_ACTION, Actions::NO_ACTION, false);
        $deleted = $this->createRow(self::SIBLING_ID + 1, Actions::DELETE, Actions::NO_ACTION, false);

        $this->assertSame(0, $this->action->queueSiblingUpserts([self::CHANGED_ID], [self::SITE]));
        $this->assertSame(Actions::NO_ACTION, $this->reload($neverSent)->getNextAction());
        $this->assertSame(Actions::NO_ACTION, $this->reload($deleted)->getNextAction());
    }

    /**
     * Only the requested site is re-queued.
     */
    public function testOtherSiteIsLeftAlone(): void
    {
        $this->createRow(self::CHANGED_ID, Actions::UPSERT, Actions::NO_ACTION, true);
        $sameSite = $this->createRow(self::SIBLING_ID, Actions::UPSERT, Actions::NO_ACTION, true);
        $otherSite = $this->createRow(self::SIBLING_ID, Actions::UPSERT, Actions::NO_ACTION, true, self::OTHER_SITE);

        $this->action->queueSiblingUpserts([self::CHANGED_ID], [self::SITE]);

        $this->assertSame(Actions::UPSERT, $this->reload($sameSite)->getNextAction());
        $this->assertSame(Actions::NO_ACTION, $this->reload($otherSite)->getNextAction());
    }

    /**
     * A product without a parent has no siblings.
     */
    public function testProductWithoutParentQueuesNothing(): void
    {
        $this->createRow(self::CHANGED_ID, Actions::UPSERT, Actions::NO_ACTION, true, self::SITE, null);
        $unrelated = $this->createRow(self::SIBLING_ID, Actions::UPSERT, Actions::NO_ACTION, true);

        $this->assertSame(0, $this->action->queueSiblingUpserts([self::CHANGED_ID], [self::SITE]));
        $this->assertSame(Actions::NO_ACTION, $this->reload($unrelated)->getNextAction());
    }

    /**
     * A live sibling whose row still stores a stale parent (null: created while standalone, or
     * an old parent) is found through the current configurable link and re-queued, on the
     * requested site only.
     *
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     */
    public function testSiblingWithStaleStoredParentIsFoundThroughCurrentLink(): void
    {
        [$parentId, $changedId, $siblingId] = $this->getConfigurableFixtureIds();
        $this->createRow($changedId, Actions::UPSERT, Actions::NO_ACTION, true, self::SITE, $parentId);
        $wasStandalone = $this->createRow($siblingId, Actions::UPSERT, Actions::NO_ACTION, true, self::SITE, null);
        $oldParentOtherSite = $this->createRow(
            $siblingId,
            Actions::UPSERT,
            Actions::NO_ACTION,
            true,
            self::OTHER_SITE,
            self::OLD_PARENT_ID
        );

        $count = $this->action->queueSiblingUpserts([$changedId], [self::SITE]);

        $this->assertSame(1, $count);
        $this->assertSame(
            Actions::UPSERT,
            $this->reload($wasStandalone)->getNextAction(),
            'Sibling row without a stored parent must be re-queued through the current link'
        );
        $this->assertSame(
            Actions::NO_ACTION,
            $this->reload($oldParentOtherSite)->getNextAction(),
            'Sibling row on a site that was not requested must be untouched'
        );
    }

    /**
     * Without a site filter every eligible sibling row is re-queued, including one that stores an
     * old parent id; the changed variant itself is not.
     *
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     */
    public function testSiblingWithOldStoredParentIsRequeuedOnEverySite(): void
    {
        [$parentId, $changedId, $siblingId] = $this->getConfigurableFixtureIds();
        $changed = $this->createRow($changedId, Actions::UPSERT, Actions::NO_ACTION, true, self::SITE, $parentId);
        $wasStandalone = $this->createRow($siblingId, Actions::UPSERT, Actions::NO_ACTION, true, self::SITE, null);
        $oldParent = $this->createRow(
            $siblingId,
            Actions::UPSERT,
            Actions::NO_ACTION,
            true,
            self::OTHER_SITE,
            self::OLD_PARENT_ID
        );

        $this->assertSame(2, $this->action->queueSiblingUpserts([$changedId]));
        $this->assertSame(Actions::UPSERT, $this->reload($wasStandalone)->getNextAction());
        $this->assertSame(Actions::UPSERT, $this->reload($oldParent)->getNextAction());
        $this->assertSame(Actions::NO_ACTION, $this->reload($changed)->getNextAction());
    }

    /**
     * The link-based match keeps the same filters: a linked sibling with a pending Delete keeps
     * it, and one that was never sent is not pushed.
     *
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     */
    public function testLinkedSiblingKeepsPendingDeleteAndUnsentState(): void
    {
        [$parentId, $changedId, $siblingId] = $this->getConfigurableFixtureIds();
        $this->createRow($changedId, Actions::UPSERT, Actions::NO_ACTION, true, self::SITE, $parentId);
        $pendingDelete = $this->createRow($siblingId, Actions::UPSERT, Actions::DELETE, true, self::SITE, null);
        $neverSent = $this->createRow(
            $siblingId,
            Actions::NO_ACTION,
            Actions::NO_ACTION,
            false,
            self::OTHER_SITE,
            self::OLD_PARENT_ID
        );

        $this->assertSame(0, $this->action->queueSiblingUpserts([$changedId]));
        $this->assertSame(Actions::DELETE, $this->reload($pendingDelete)->getNextAction());
        $this->assertSame(Actions::NO_ACTION, $this->reload($neverSent)->getNextAction());
    }

    /**
     * Parent entity id and two linked child ids from the product_configurable.php fixture.
     *
     * @return int[] [parent id, changed child id, sibling child id]
     */
    private function getConfigurableFixtureIds(): array
    {
        $parent = $this->objectManager->get(ProductRepositoryInterface::class)->get('configurable', false, null, true);
        $childIds = array_values(array_map('intval', $parent->getTypeInstance()->getUsedProductIds($parent)));
        sort($childIds);
        $this->assertGreaterThanOrEqual(2, count($childIds), 'Fixture must link at least two variants');

        return [(int)$parent->getId(), $childIds[0], $childIds[1]];
    }

    /**
     * A variant that left website B is queued for Delete on site B; its stored parent still
     * locates the live sibling, which is re-queued on site B only, and the Delete stays pending.
     *
     * @magentoDataFixture Magento/Store/_files/second_website_with_store_group_and_store.php
     */
    public function testVariantLeavingWebsiteRequeuesSiblingOnDepartedSiteOnly(): void
    {
        $this->configureTwoSites();
        // The variant is on website A only: it has left website B (site B).
        $variantId = $this->createSimpleProduct('sib-left-website', [$this->getWebsiteId('base')]);
        $rows = $this->createVariantAndSiblingRows($variantId);

        $this->action->queueDeleteForUnassignedSites([$variantId]);

        $this->assertLeftSiteBOnly($rows);
    }

    /**
     * Same case through the admin grid mass action "remove from website", which reaches
     * queueDeleteForUnassignedSites() via ProductActionPlugin::afterUpdateWebsites().
     *
     * Runs without DB isolation: updateWebsites() reindexes category products, which reads the
     * store index table the fixture created, and MySQL rejects that inside the same transaction
     * (error 1412). The product and rows are removed at the end; the fixture rolls back the website.
     *
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento/Store/_files/second_website_with_store_group_and_store.php
     */
    public function testMassActionRemoveFromWebsiteRequeuesSiblingOnDepartedSiteOnly(): void
    {
        $this->configureTwoSites();
        $websiteB = $this->getWebsiteId('test');
        $variantId = $this->createSimpleProduct('sib-mass-remove', [$this->getWebsiteId('base'), $websiteB]);
        try {
            $rows = $this->createVariantAndSiblingRows($variantId);

            $this->objectManager->get(ProductAction::class)->updateWebsites([$variantId], [$websiteB], 'remove');

            $this->assertLeftSiteBOnly($rows);
        } finally {
            $this->deleteRows([$variantId, self::SIBLING_ID]);
            $this->deleteProduct('sib-mass-remove');
        }
    }

    /**
     * Rows on sites A and B for the variant and a synthetic sibling, all live in Athos and idle.
     * The parent is known only from the stored target_parent_id (no product link exists).
     *
     * @param int $variantId
     * @return array<string, IndexingEntity>
     */
    private function createVariantAndSiblingRows(int $variantId): array
    {
        return [
            'variant_a' => $this->createRow($variantId, Actions::UPSERT, Actions::NO_ACTION, true, self::SITE_A),
            'variant_b' => $this->createRow($variantId, Actions::UPSERT, Actions::NO_ACTION, true, self::SITE_B),
            'sibling_a' => $this->createRow(self::SIBLING_ID, Actions::UPSERT, Actions::NO_ACTION, true, self::SITE_A),
            'sibling_b' => $this->createRow(self::SIBLING_ID, Actions::UPSERT, Actions::NO_ACTION, true, self::SITE_B),
        ];
    }

    /**
     * Expected state after the variant left website B.
     *
     * @param array<string, IndexingEntity> $rows
     * @return void
     */
    private function assertLeftSiteBOnly(array $rows): void
    {
        $this->assertSame(
            Actions::DELETE,
            $this->reload($rows['variant_b'])->getNextAction(),
            'Variant Delete on the departed site must stay pending'
        );
        $this->assertSame(
            Actions::UPSERT,
            $this->reload($rows['sibling_b'])->getNextAction(),
            'Sibling on the departed site must be re-queued'
        );
        $this->assertSame(
            Actions::NO_ACTION,
            $this->reload($rows['variant_a'])->getNextAction(),
            'Variant on the site it still serves must be untouched'
        );
        $this->assertSame(
            Actions::NO_ACTION,
            $this->reload($rows['sibling_a'])->getNextAction(),
            'Sibling on the site the variant still serves must be untouched'
        );
    }

    /**
     * Site A on the default store view (website A), site B on the fixture store view (website B).
     *
     * @return void
     */
    private function configureTwoSites(): void
    {
        /** @var MutableScopeConfigInterface $config */
        $config = $this->objectManager->get(MutableScopeConfigInterface::class);
        foreach (['default' => self::SITE_A, self::SECOND_STORE => self::SITE_B] as $storeCode => $siteId) {
            $config->setValue(Constants::XML_PATH_CONFIG_SITE_ID, $siteId, ScopeInterface::SCOPE_STORE, $storeCode);
            $config->setValue(Constants::XML_PATH_LIVE_INDEXING_ENABLED, 1, ScopeInterface::SCOPE_STORE, $storeCode);
        }
    }

    /**
     * Website id by code.
     *
     * @param string $code
     * @return int
     */
    private function getWebsiteId(string $code): int
    {
        return (int)$this->objectManager->get(StoreManagerInterface::class)->getWebsite($code)->getId();
    }

    /**
     * Simple product assigned to the given websites.
     *
     * @param string $sku
     * @param int[] $websiteIds
     * @return int
     */
    private function createSimpleProduct(string $sku, array $websiteIds): int
    {
        /** @var ProductInterface $product */
        $product = $this->objectManager->get(ProductInterfaceFactory::class)->create();
        $product->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId(4)
            ->setSku($sku)
            ->setName($sku)
            ->setPrice(10)
            ->setStatus(Status::STATUS_ENABLED)
            ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
            ->setWebsiteIds($websiteIds);

        return (int)$this->objectManager->get(ProductRepositoryInterface::class)->save($product)->getId();
    }

    /**
     * Create an indexing row for a variant of PARENT_ID (or no parent when null is given).
     *
     * @param int $targetId
     * @param string $lastAction
     * @param string $nextAction
     * @param bool $isIndexable
     * @param string $siteId
     * @param int|null $parentId
     * @return IndexingEntity
     */
    private function createRow(
        int $targetId,
        string $lastAction,
        string $nextAction,
        bool $isIndexable,
        string $siteId = self::SITE,
        ?int $parentId = self::PARENT_ID
    ): IndexingEntity {
        /** @var IndexingEntity $entity */
        $entity = $this->objectManager->create(IndexingEntity::class);
        $entity->setTargetEntityType(Constants::PRODUCT_KEY);
        $entity->setTargetId($targetId);
        $entity->setTargetParentId($parentId);
        $entity->setSiteId($siteId);
        $entity->setLastAction($lastAction);
        $entity->setNextAction($nextAction);
        $entity->setIsIndexable($isIndexable);
        $this->objectManager->get(IndexingEntityResourceModel::class)->save($entity);

        return $entity;
    }

    /**
     * Remove the indexing rows of these target ids (tests without DB isolation).
     *
     * @param int[] $targetIds
     * @return void
     */
    private function deleteRows(array $targetIds): void
    {
        $resource = $this->objectManager->get(IndexingEntityResourceModel::class);
        $resource->getConnection()->delete(
            $resource->getMainTable(),
            ['target_id IN (?)' => $targetIds, 'site_id IN (?)' => [self::SITE_A, self::SITE_B]]
        );
    }

    /**
     * Delete a product created by a test without DB isolation.
     *
     * @param string $sku
     * @return void
     */
    private function deleteProduct(string $sku): void
    {
        /** @var \Magento\Framework\Registry $registry */
        $registry = $this->objectManager->get(\Magento\Framework\Registry::class);
        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', true);
        try {
            $this->objectManager->get(ProductRepositoryInterface::class)->deleteById($sku);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            // Already removed.
        } finally {
            $registry->unregister('isSecureArea');
        }
    }

    /**
     * Fresh copy of the row from the database.
     *
     * @param IndexingEntity $entity
     * @return IndexingEntity
     */
    private function reload(IndexingEntity $entity): IndexingEntity
    {
        /** @var IndexingEntity $fresh */
        $fresh = $this->objectManager->create(IndexingEntity::class);
        $this->objectManager->get(IndexingEntityResourceModel::class)->load($fresh, $entity->getId());

        return $fresh;
    }
}
