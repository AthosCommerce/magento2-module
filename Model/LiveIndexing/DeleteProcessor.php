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

namespace AthosCommerce\Feed\Model\LiveIndexing;

use AthosCommerce\Feed\Api\LiveIndexing\DeleteEntityHandlerInterface;
use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Service\Action\UpdateIndexingEntitiesActionsActionInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

/**
 * Processes a batch of pending-DELETE indexing entities for a single store.
 *
 * Responsibilities:
 *  1. Map each IndexingEntity record to the correct API entity identifier
 *     (plain target_id for standalone products, "parentId_childId" for children).
 *  2. Send individual delete requests to the remote API.
 *  3. Mark only the successfully deleted indexing rows in the local table.
 */
class DeleteProcessor
{
    /**
     * @var DeleteEntityHandlerInterface
     */
    private $deleteHandler;
    /**
     * @var UpdateIndexingEntitiesActionsActionInterface
     */
    private $updateIndexingEntitiesActionsAction;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param DeleteEntityHandlerInterface $deleteHandler
     * @param UpdateIndexingEntitiesActionsActionInterface $updateIndexingEntitiesActionsAction
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        DeleteEntityHandlerInterface                  $deleteHandler,
        UpdateIndexingEntitiesActionsActionInterface  $updateIndexingEntitiesActionsAction,
        AthosCommerceLogger                          $logger
    ) {
        $this->deleteHandler                       = $deleteHandler;
        $this->updateIndexingEntitiesActionsAction  = $updateIndexingEntitiesActionsAction;
        $this->logger                              = $logger;
    }

    /**
     * Process all pending-DELETE records for one store and return the success count.
     *
     * @param IndexingEntity[] $deleteRecords  Records fetched by Processor
     * @param string           $siteId
     * @param string           $storeCode      Used for logging only
     *
     * @return int  Number of successfully deleted indexing rows
     */
    public function execute(array $deleteRecords, string $siteId, string $storeCode): int
    {
        $items = $this->buildDeleteItems($deleteRecords);

        if (empty($items)) {
            return 0;
        }

        [$successApiIds, $failedApiIds, $successEntityIds] =
            $this->sendDeleteRequests($items, $siteId, $storeCode);

        if (!empty($successEntityIds)) {
            $this->updateIndexingEntitiesActionsAction->execute(
                array_values(array_unique($successEntityIds)),
                $siteId,
                Actions::DELETE,
                IndexingEntity::ENTITY_ID
            );
            $this->logger->info(
                '[LiveIndexing][DELETE] Action updates completed successfully',
                [
                    'siteId'            => $siteId,
                    'store'             => $storeCode,
                    'successEntityIds'  => $successEntityIds,
                ]
            );
        }

        return count($successApiIds);
    }

    /**
     * Maps each IndexingEntity record to the API identifier and its indexing row id.
     *
     * For child products (configurable/grouped) the API id is "parentId_childId",
     * matching the entity_id produced by EntityIdProvider on UPSERT.
     * For standalone products it is the plain string representation of target_id.
     *
     * @param IndexingEntity[] $deleteRecords
     * @return array<array{apiId: string, entityId: int}>
     */
    private function buildDeleteItems(array $deleteRecords): array
    {
        $items = [];
        foreach ($deleteRecords as $record) {
            if (!$record instanceof IndexingEntity) {
                continue;
            }
            $targetId = (int)$record->getTargetId();
            $parentId = $record->getTargetParentId();
            $apiId    = $parentId !== null
                ? ((int)$parentId . '_' . $targetId)
                : (string)$targetId;

            $items[] = ['apiId' => $apiId, 'entityId' => (int)$record->getId()];
        }
        return $items;
    }

    /**
     * Sends a delete request to the API for each item.
     *
     * Returns three parallel arrays:
     *  [0] successApiIds   — composite/plain API ids that succeeded
     *  [1] failedApiIds    — those that failed
     *  [2] successEntityIds — indexing row ids for DB update
     *
     * @param array  $items
     * @param string $siteId
     * @param string $storeCode
     *
     * @return array{0: string[], 1: string[], 2: int[]}
     */
    private function sendDeleteRequests(array $items, string $siteId, string $storeCode): array
    {
        $successApiIds   = [];
        $failedApiIds    = [];
        $successEntityIds = [];

        $this->logger->info(
            '[LiveIndexing] DELETE operation started',
            [
                'siteId' => $siteId,
                'store'  => $storeCode,
                'count'  => count($items),
            ]
        );

        foreach ($items as $item) {
            $apiId    = $item['apiId'];
            $entityId = $item['entityId'];
            try {
                if ($this->deleteHandler->process($apiId)) {
                    $successApiIds[]   = $apiId;
                    $successEntityIds[] = $entityId;
                } else {
                    $failedApiIds[] = $apiId;
                }
            } catch (\Throwable $e) {
                $failedApiIds[] = $apiId;
                $this->logger->error(
                    sprintf('Exception thrown while DELETION for ID(%s)', $apiId),
                    [
                        'siteId' => $siteId,
                        'store'  => $storeCode,
                        'error'  => $e->getMessage(),
                    ]
                );
            }
        }

        $this->logger->info(
            '[LiveIndexing] DELETE operation completed',
            [
                'siteId'     => $siteId,
                'store'      => $storeCode,
                'successIds' => $successApiIds,
                'failedIds'  => $failedApiIds,
            ]
        );

        return [$successApiIds, $failedApiIds, $successEntityIds];
    }
}
