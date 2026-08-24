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

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Api\LiveIndexing\UpsertEntityHandlerInterface;
use AthosCommerce\Feed\Model\CollectionProcessor as CollectionProcessorModel;
use AthosCommerce\Feed\Model\Config as ConfigModel;
use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Model\ItemsGenerator;
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Service\Action\UpdateIndexingEntitiesActionsActionInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Processes a batch of pending-UPSERT indexing entities for a single store.
 *
 * Responsibilities:
 *  1. Resolve Magento product models from the pending target_ids via a collection.
 *  2. Generate API payloads through the feed's item pipeline.
 *  3. Send payloads to the remote API in rate-limited chunks.
 *  4. Mark successfully upserted entities in the local indexing table.
 */
class UpdateProcessor
{
    private const MAX_DB_FETCH = 960;

    /**
     * @var CollectionProcessorModel
     */
    private $collectionProcessor;
    /**
     * @var ItemsGenerator
     */
    private $itemsGenerator;
    /**
     * @var UpsertEntityHandlerInterface
     */
    private $upsertHandler;
    /**
     * @var UpdateIndexingEntitiesActionsActionInterface
     */
    private $updateIndexingEntitiesActionsAction;
    /**
     * @var ConfigModel
     */
    private $config;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param CollectionProcessorModel $collectionProcessor
     * @param ItemsGenerator $itemsGenerator
     * @param UpsertEntityHandlerInterface $upsertHandler
     * @param UpdateIndexingEntitiesActionsActionInterface $updateIndexingEntitiesActionsAction
     * @param ConfigModel $config
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        CollectionProcessorModel                     $collectionProcessor,
        ItemsGenerator                               $itemsGenerator,
        UpsertEntityHandlerInterface                 $upsertHandler,
        UpdateIndexingEntitiesActionsActionInterface  $updateIndexingEntitiesActionsAction,
        ConfigModel                                  $config,
        AthosCommerceLogger                          $logger
    ) {
        $this->collectionProcessor                 = $collectionProcessor;
        $this->itemsGenerator                      = $itemsGenerator;
        $this->upsertHandler                       = $upsertHandler;
        $this->updateIndexingEntitiesActionsAction  = $updateIndexingEntitiesActionsAction;
        $this->config                              = $config;
        $this->logger                              = $logger;
    }

    /**
     * Process all pending-UPSERT records for one store and return the success count.
     *
     * @param IndexingEntity[]           $updateRecords    Records fetched by Processor
     * @param StoreInterface             $store
     * @param string                     $siteId
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return int  Number of successfully upserted entities
     */
    public function execute(
        array                      $updateRecords,
        StoreInterface             $store,
        string                     $siteId,
        FeedSpecificationInterface $feedSpecification
    ): int {
        if (empty($updateRecords)) {
            return 0;
        }

        $storeId   = (int)$store->getId();
        $storeCode = $store->getCode();

        $payloads = $this->buildPayloads($updateRecords, $feedSpecification, $siteId, $storeCode);

        if (empty($payloads)) {
            return 0;
        }

        $chunkSize = $this->config->getChunkSizeByStoreId($storeId);
        $delayMs   = $this->config->getMillisecondsDelayByStoreId($storeId);

        [$successIds, $failedIds] = $this->sendUpsertRequests($payloads, $chunkSize, $delayMs, $siteId, $storeCode);

        $successIds = array_values(array_unique($successIds));
        if (!empty($successIds)) {
            $this->updateIndexingEntitiesActionsAction->execute(
                $successIds,
                $siteId,
                Actions::UPSERT,
                IndexingEntity::TARGET_ID
            );
            $this->logger->info(
                '[LiveIndexing][UPDATE] Action updates completed successfully',
                [
                    'siteId'           => $siteId,
                    'store'            => $storeCode,
                    'successUpdateIds' => $successIds,
                ]
            );
        }

        return count($successIds);
    }

    /**
     * Loads the product collection for the given update records and runs the feed
     * item pipeline to produce API-ready payloads.
     *
     * @param IndexingEntity[]           $updateRecords
     * @param FeedSpecificationInterface $feedSpecification
     * @param string                     $siteId
     * @param string                     $storeCode
     *
     * @return array
     */
    private function buildPayloads(
        array                      $updateRecords,
        FeedSpecificationInterface $feedSpecification,
        string                     $siteId,
        string                     $storeCode
    ): array {
        $targetIds = [];
        foreach ($updateRecords as $record) {
            if ($record instanceof IndexingEntity) {
                $targetIds[] = (int)$record->getTargetId();
            }
        }

        if (empty($targetIds)) {
            return [];
        }

        $startTimestamp = microtime(true);

        $collection = $this->collectionProcessor->getCollection($feedSpecification);
        $collection->addFieldToFilter('entity_id', ['in' => $targetIds]);
        $collection->setPageSize(min(count($targetIds), self::MAX_DB_FETCH));
        $collection->load();

        $this->collectionProcessor->processAfterLoad($collection, $feedSpecification);

        $this->logger->debug(
            '[LiveIndexing][Update][Collection Query]',
            [
                'siteId'                  => $siteId,
                'store'                   => $storeCode,
                'query'                   => $collection->getSelect()->__toString(),
                'timeTakenForCollection'  => microtime(true) - $startTimestamp,
            ]
        );

        $this->itemsGenerator->resetDataProviders($feedSpecification);
        $startTimestamp = microtime(true);
        $items = $this->itemsGenerator->generate($collection->getItems(), $feedSpecification);
        $this->itemsGenerator->resetDataProvidersAfterFetchItems($feedSpecification);

        $this->logger->debug(
            '[LiveIndexing][Update][ItemGeneration]',
            [
                'siteId'                    => $siteId,
                'store'                     => $storeCode,
                'timeTakenForItemGeneration' => microtime(true) - $startTimestamp,
            ]
        );

        return iterator_to_array($items, false);
    }

    /**
     * Sends upsert payloads to the API in rate-limited chunks.
     *
     * @param array  $payloads
     * @param int    $chunkSize
     * @param int    $delayMs
     * @param string $siteId
     * @param string $storeCode
     *
     * @return array{0: int[], 1: int[]}  [successIds, failedIds]
     */
    private function sendUpsertRequests(
        array  $payloads,
        int    $chunkSize,
        int    $delayMs,
        string $siteId,
        string $storeCode
    ): array {
        $successIds  = [];
        $failedIds   = [];
        $batchChunks = array_chunk($payloads, $chunkSize);
        $totalChunks = count($batchChunks);

        foreach ($batchChunks as $chunkIndex => $chunk) {
            $this->logger->info(
                sprintf('[LiveIndexing] UPDATE processing chunk %s of %s', $chunkIndex + 1, $totalChunks),
                [
                    'siteId'    => $siteId,
                    'store'     => $storeCode,
                    'chunkSize' => count($chunk),
                ]
            );

            foreach ($chunk as $payload) {
                if (!$payload) {
                    continue;
                }
                $id = $this->extractEntityId($payload);
                try {
                    if ($this->upsertHandler->process($payload)) {
                        $successIds[] = $id;
                    } else {
                        $failedIds[] = $id;
                    }
                } catch (\Throwable $e) {
                    $failedIds[] = $id;
                    $this->logger->error(
                        sprintf('Exception thrown while UPDATE for ID(%s)', $id),
                        [
                            'siteId' => $siteId,
                            'store'  => $storeCode,
                            'error'  => $e->getMessage(),
                        ]
                    );
                }
            }

            if ($delayMs > 0 && ($chunkIndex + 1) < $totalChunks) {
                // Cap at 1 second (1000 ms) to avoid excessively long sleeps.
                usleep(min($delayMs, 1000));
            }
        }

        $this->logger->info(
            '[LiveIndexing] UPDATE operation completed',
            [
                'siteId'     => $siteId,
                'store'      => $storeCode,
                'chunks'     => $totalChunks,
                'successIds' => $successIds,
                'failedIds'  => $failedIds,
            ]
        );

        return [$successIds, $failedIds];
    }

    /**
     * @param mixed $payload
     * @return int
     */
    private function extractEntityId($payload): int
    {
        if (is_object($payload)) {
            if (method_exists($payload, 'getEntityId')) {
                return (int)$payload->getEntityId();
            }
            if (method_exists($payload, 'getId')) {
                return (int)$payload->getId();
            }
            if (property_exists($payload, 'entity_id')) {
                return (int)$payload->entity_id;
            }
        }

        if (is_array($payload)) {
            return (int)($payload['entity_id'] ?? $payload['id'] ?? 0);
        }

        return 0;
    }
}
