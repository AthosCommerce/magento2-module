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
use Magento\Framework\App\ResourceConnection;

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
     * @param ResourceConnection $resourceConnection
     * @param LiveIndexingSiteProvider $siteProvider
     * @param SetIndexingEntitiesToDeleteActionInterface $setIndexingEntitiesToDeleteAction
     * @param AddIndexingEntitiesActionInterface $addIndexingEntitiesAction
     * @param MagentoEntityInterfaceFactory $magentoEntityFactory
     * @param RelationsProvider $relationsProvider
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LiveIndexingSiteProvider $siteProvider,
        SetIndexingEntitiesToDeleteActionInterface $setIndexingEntitiesToDeleteAction,
        AddIndexingEntitiesActionInterface $addIndexingEntitiesAction,
        MagentoEntityInterfaceFactory $magentoEntityFactory,
        RelationsProvider $relationsProvider,
        AthosCommerceLogger $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->siteProvider = $siteProvider;
        $this->setIndexingEntitiesToDeleteAction = $setIndexingEntitiesToDeleteAction;
        $this->addIndexingEntitiesAction = $addIndexingEntitiesAction;
        $this->magentoEntityFactory = $magentoEntityFactory;
        $this->relationsProvider = $relationsProvider;
        $this->logger = $logger;
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
        }
        if ($idsBySite) {
            $this->logger->debug('[SyncSiteAssignment] Delete for unassigned sites', ['sites' => $idsBySite]);
        }
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
