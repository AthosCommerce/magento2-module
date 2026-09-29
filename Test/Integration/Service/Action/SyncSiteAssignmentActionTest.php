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
use Magento\Framework\ObjectManagerInterface;
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
 */
class SyncSiteAssignmentActionTest extends TestCase
{
    private const PARENT_ID = 990000001;
    private const CHANGED_ID = 990000002;
    private const SIBLING_ID = 990000003;
    private const SITE = 'test-siblings-site';
    private const OTHER_SITE = 'test-siblings-other';

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
