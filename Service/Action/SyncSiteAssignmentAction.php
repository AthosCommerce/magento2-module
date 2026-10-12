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

namespace AthosCommerce\Feed\Service\Action;

use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Api\MagentoEntityInterfaceFactory;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\RelationsProvider;
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Service\Provider\LiveIndexingSiteProvider;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Keeps indexing rows in line with website assignments: Delete on sites a product left,
 * new rows on sites it joined.
 */
class SyncSiteAssignmentAction
{
    /**
     * Parent types have no row of their own: the feed sends their variants.
     */
    private const PARENT_TYPES = ['configurable'];

    /**
     * catalog_product_link type of grouped product associations.
     */
    private const GROUPED_LINK_TYPE_ID = 3;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var LiveIndexingSiteProvider
     */
    private $siteProvider;
    /**
     * @var SetIndexingEntitiesToDeleteActionInterface
     */
    private $setIndexingEntitiesToDeleteAction;
    /**
     * @var AddIndexingEntitiesActionInterface
     */
    private $addIndexingEntitiesAction;
    /**
     * @var MagentoEntityInterfaceFactory
     */
    private $magentoEntityFactory;
    /**
     * @var RelationsProvider
     */
    private $relationsProvider;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LiveIndexingSiteProvider $siteProvider
     * @param SetIndexingEntitiesToDeleteActionInterface $setIndexingEntitiesToDeleteAction
     * @param AddIndexingEntitiesActionInterface $addIndexingEntitiesAction
     * @param MagentoEntityInterfaceFactory $magentoEntityFactory
     * @param RelationsProvider $relationsProvider
     * @param AthosCommerceLogger $logger
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LiveIndexingSiteProvider $siteProvider,
        SetIndexingEntitiesToDeleteActionInterface $setIndexingEntitiesToDeleteAction,
        AddIndexingEntitiesActionInterface $addIndexingEntitiesAction,
        MagentoEntityInterfaceFactory $magentoEntityFactory,
        RelationsProvider $relationsProvider,
        AthosCommerceLogger $logger,
        MetadataPool $metadataPool
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->siteProvider = $siteProvider;
        $this->setIndexingEntitiesToDeleteAction = $setIndexingEntitiesToDeleteAction;
        $this->addIndexingEntitiesAction = $addIndexingEntitiesAction;
        $this->magentoEntityFactory = $magentoEntityFactory;
        $this->relationsProvider = $relationsProvider;
        $this->logger = $logger;
        $this->metadataPool = $metadataPool;
    }

    /**
     * Queue Delete on every site a product has a live row for but is no longer assigned to.
     *
     * Only site ids some store view is configured for are considered: rows of other site ids
     * are never synced, so a Delete there would do nothing.
     *
     * @param int[] $productIds
     * @param string[] $siteIds limit to these sites; all configured sites when empty
     * @return void
     */
    public function queueDeleteForUnassignedSites(array $productIds, array $siteIds = []): void
    {
        if (!$productIds) {
            return;
        }
        $configuredSiteIds = [];
        foreach ($this->siteProvider->getSiteIdsByWebsite() as $websiteSiteIds) {
            foreach ($websiteSiteIds as $configuredSiteId) {
                $configuredSiteIds[$configuredSiteId] = $configuredSiteId;
            }
        }
        $siteIds = $siteIds
            ? array_values(array_intersect($siteIds, $configuredSiteIds))
            : array_values($configuredSiteIds);
        if (!$siteIds) {
            return;
        }
        $served = $this->siteProvider->getServedSiteIds($productIds);
        $idsBySite = [];
        foreach ($this->getPendingDeletionRows($productIds, $siteIds) as $row) {
            $productId = (int)$row['target_id'];
            if (!in_array($row['site_id'], $served[$productId] ?? [], true)) {
                $idsBySite[$row['site_id']][$productId] = $productId;
            }
        }
        foreach ($idsBySite as $siteId => $ids) {
            $this->setIndexingEntitiesToDeleteAction->execute(array_values($ids), [(string)$siteId]);
            // The variant left this site: its siblings' variant-wide values change there.
            $this->queueSiblingUpserts(array_values($ids), [(string)$siteId]);
        }
        if ($idsBySite) {
            $this->logger->debug('[SyncSiteAssignment] Delete for unassigned sites', ['sites' => $idsBySite]);
        }
    }

    /**
     * Re-queue Upsert for the other variants of the changed products' parents.
     *
     * Variant payloads carry values calculated across all variants of the parent (e.g.
     * ss_minimums / ss_maximums), so a change to one variant makes its siblings' payloads stale.
     * Only siblings that are live in Athos and idle are re-queued (indexable, last action Upsert,
     * no pending action), so a pending Delete or a never-sent row is left alone. Parents come
     * from the changed products' rows, so a deleted or unlinked variant still finds them, and
     * from the current relations, so a newly linked variant does too.
     *
     * Siblings are matched two ways, each with the same filters: rows that store one of the
     * parents, and rows of the parents' currently linked children. The second catches live
     * siblings whose row was created while they were standalone or under another parent and
     * still holds a null or old target_parent_id.
     *
     * @param int[] $productIds changed products (variants; other products are ignored)
     * @param string[] $siteIds limit to these sites; all sites when empty
     * @return int number of sibling rows queued
     */
    public function queueSiblingUpserts(array $productIds, array $siteIds = []): int
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if (!$productIds) {
            return 0;
        }
        $parentIds = $this->getParentIds($productIds, $siteIds);
        if (!$parentIds) {
            return 0;
        }
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('athoscommerce_indexing_entity');
        $where = [
            'target_entity_type = ?' => Constants::PRODUCT_KEY,
            'target_id NOT IN (?)' => $productIds,
            'is_indexable = ?' => 1,
            'last_action = ?' => Actions::UPSERT,
            'next_action = ?' => Actions::NO_ACTION,
        ];
        if ($siteIds) {
            $where['site_id IN (?)'] = $siteIds;
        }
        // Rows that store the parent (index on target_entity_type, target_parent_id, site_id).
        $count = (int)$connection->update(
            $table,
            ['next_action' => Actions::UPSERT],
            $where + ['target_parent_id IN (?)' => $parentIds]
        );
        // Rows of the currently linked children (unique key on target_entity_type, target_id, ...).
        // Rows re-queued above no longer have an empty next_action, so none is counted twice.
        $linkedChildIds = array_values(array_diff($this->getLinkedChildIds($parentIds), $productIds));
        if ($linkedChildIds) {
            $count += (int)$connection->update(
                $table,
                ['next_action' => Actions::UPSERT],
                $where + ['target_id IN (?)' => $linkedChildIds]
            );
        }
        if ($count) {
            $this->logger->debug(
                '[SyncSiteAssignment] Sibling variants re-queued',
                ['product_ids' => $productIds, 'parent_ids' => $parentIds, 'site_ids' => $siteIds, 'rows' => $count]
            );
        }

        return $count;
    }

    /**
     * Entity ids of the children currently linked to the parents (configurable and grouped).
     *
     * Parents are entity ids; link tables reference the parent by its link field (row_id on
     * Adobe Commerce). Children with required options are included, unlike Magento's own
     * getChildrenIds() helpers, because they are variants all the same.
     *
     * @param int[] $parentIds
     * @return int[]
     */
    private function getLinkedChildIds(array $parentIds): array
    {
        $connection = $this->resourceConnection->getConnection();
        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');

        $configurable = $connection->select()
            ->from(['l' => $this->resourceConnection->getTableName('catalog_product_super_link')], ['product_id'])
            ->join(['p' => $productTable], sprintf('p.%s = l.parent_id', $linkField), [])
            ->where('p.entity_id IN (?)', $parentIds);
        $grouped = $connection->select()
            ->from(['l' => $this->resourceConnection->getTableName('catalog_product_link')], ['linked_product_id'])
            ->join(['p' => $productTable], sprintf('p.%s = l.product_id', $linkField), [])
            ->where('p.entity_id IN (?)', $parentIds)
            ->where('l.link_type_id = ?', self::GROUPED_LINK_TYPE_ID);

        return array_values(array_unique(array_map(
            'intval',
            array_merge($connection->fetchCol($configurable), $connection->fetchCol($grouped))
        )));
    }

    /**
     * Parent entity ids of the products, from their indexing rows and their current relations.
     *
     * @param int[] $productIds
     * @param string[] $siteIds
     * @return int[]
     */
    private function getParentIds(array $productIds, array $siteIds): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->distinct()
            ->from($this->resourceConnection->getTableName('athoscommerce_indexing_entity'), ['target_parent_id'])
            ->where('target_entity_type = ?', Constants::PRODUCT_KEY)
            ->where('target_id IN (?)', $productIds)
            ->where('target_parent_id > 0');
        if ($siteIds) {
            $select->where('site_id IN (?)', $siteIds);
        }
        $parentIds = array_map('intval', $connection->fetchCol($select));
        $relations = array_merge(
            $this->relationsProvider->getConfigurableRelationIds($productIds),
            $this->relationsProvider->getGroupRelationIds($productIds)
        );
        foreach ($relations as $relation) {
            $parentIds[] = (int)$relation['parent_entity_id'];
        }

        return array_values(array_unique(array_filter($parentIds)));
    }

    /**
     * Whether the product has an indexing row on any site.
     *
     * @param int $productId
     * @return bool
     */
    public function hasRows(int $productId): bool
    {
        $connection = $this->resourceConnection->getConnection();

        return (bool)$connection->fetchOne(
            $connection->select()
                ->from($this->resourceConnection->getTableName('athoscommerce_indexing_entity'), ['entity_id'])
                ->where('target_entity_type = ?', Constants::PRODUCT_KEY)
                ->where('target_id = ?', $productId)
                ->limit(1)
        );
    }

    /**
     * Drop ids whose row on this site is already deleted, queued for Delete, or never sent.
     *
     * Without this, every discovery run would queue the same Delete again.
     *
     * @param int[] $productIds
     * @param string $siteId
     * @return int[]
     */
    public function filterPendingDeletions(array $productIds, string $siteId): array
    {
        return array_values(array_unique(array_map(
            static fn (array $row): int => (int)$row['target_id'],
            $this->getPendingDeletionRows($productIds, [$siteId])
        )));
    }

    /**
     * Create indexable rows (next action Upsert) for products that have no row on the site yet.
     *
     * Configurable parents are skipped, as in discovery.
     *
     * @param int[] $productIds
     * @param string $siteId
     * @return int[] ids a row was created for
     */
    public function addMissingRows(array $productIds, string $siteId): array
    {
        if (!$productIds || $siteId === '') {
            return [];
        }
        $connection = $this->resourceConnection->getConnection();
        $existing = array_map('intval', $connection->fetchCol(
            $connection->select()
                ->from($this->resourceConnection->getTableName('athoscommerce_indexing_entity'), ['target_id'])
                ->where('target_entity_type = ?', Constants::PRODUCT_KEY)
                ->where('site_id = ?', $siteId)
                ->where('target_id IN (?)', $productIds)
        ));
        $missing = array_values(array_diff($productIds, $existing));
        if ($missing) {
            $missing = array_map('intval', $connection->fetchCol(
                $connection->select()
                    ->from($this->resourceConnection->getTableName('catalog_product_entity'), ['entity_id'])
                    ->where('entity_id IN (?)', $missing)
                    ->where('type_id NOT IN (?)', self::PARENT_TYPES)
            ));
        }
        if (!$missing) {
            return [];
        }

        $parentIds = [];
        $relations = array_merge(
            $this->relationsProvider->getConfigurableRelationIds($missing),
            $this->relationsProvider->getGroupRelationIds($missing)
        );
        foreach ($relations as $relation) {
            // target_parent_id holds the parent entity_id; parent_id is the row_id on Commerce.
            $parentIds[(int)$relation['product_id']] = (int)$relation['parent_entity_id'];
        }
        $entities = [];
        foreach ($missing as $productId) {
            $entities[$productId] = $this->magentoEntityFactory->create([
                'entityId' => $productId,
                'entityParentId' => $parentIds[$productId] ?? 0,
                'siteId' => $siteId,
                'isIndexable' => true,
            ]);
        }
        $this->addIndexingEntitiesAction->execute(Constants::PRODUCT_KEY, $entities);
        $this->logger->debug('[SyncSiteAssignment] Rows added', ['site_id' => $siteId, 'ids' => $missing]);

        return $missing;
    }

    /**
     * Rows that could still need a Delete: not queued for Delete, not deleted, not unsent.
     *
     * @param int[] $productIds
     * @param string[] $siteIds
     * @return array<int, array{target_id: string, site_id: string}>
     */
    private function getPendingDeletionRows(array $productIds, array $siteIds): array
    {
        if (!$productIds) {
            return [];
        }
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                $this->resourceConnection->getTableName('athoscommerce_indexing_entity'),
                ['target_id', 'site_id']
            )
            ->where('target_entity_type = ?', Constants::PRODUCT_KEY)
            ->where('target_id IN (?)', $productIds)
            ->where('next_action <> ?', Actions::DELETE)
            ->where(sprintf(
                'NOT (next_action = %1$s AND (last_action = %2$s OR (last_action = %1$s AND is_indexable = 0)))',
                $connection->quote(Actions::NO_ACTION),
                $connection->quote(Actions::DELETE)
            ));
        if ($siteIds) {
            $select->where('site_id IN (?)', $siteIds);
        }

        return $connection->fetchAll($select);
    }
}
