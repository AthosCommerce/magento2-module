<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Model\LiveIndexing;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Api\LiveIndexing\DeleteEntityHandlerInterface;
use AthosCommerce\Feed\Api\LiveIndexing\UpsertEntityHandlerInterface;
use AthosCommerce\Feed\Api\RetryManagerInterface;
use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Model\CollectionProcessor;
use AthosCommerce\Feed\Model\Config as ConfigModel;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Model\ItemsGenerator;
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Service\Action\UpdateIndexingEntitiesActionsActionInterface as UpdateIndexingEntitiesActionsAction;
use AthosCommerce\Feed\Service\Provider\IndexingEntityProvider as IndexingEntityProvider;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class Processor
{
    private const MAX_DB_FETCH = 960;

    /**
     * @var IndexingEntityProvider
     */
    private $indexingEntityProvider;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ConfigModel
     */
    private $config;
    /**
     * @var UpdateIndexingEntitiesActionsAction
     */
    private $updateIndexingEntitiesActionsAction;
    /**
     * @var DeleteEntityHandlerInterface
     */
    private $deleteHandler;
    /**
     * @var UpsertEntityHandlerInterface
     */
    private $upsertHandler;
    /**
     * @var CollectionProcessor
     */
    private $collectionProcessor;
    /**
     * @var ItemsGenerator
     */
    private $itemsGenerator;
    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var RetryManagerInterface
     */
    private $retryManager;
    /**
     * @var ContextManagerInterface
     */
    private $contextManager;

    /**
     * @param IndexingEntityProvider $indexingEntityProvider
     * @param AthosCommerceLogger $logger
     * @param StoreManagerInterface $storeManager
     * @param ConfigModel $config
     * @param UpdateIndexingEntitiesActionsAction $updateIndexingEntitiesActionsAction
     * @param DeleteEntityHandlerInterface $deleteHandler
     * @param UpsertEntityHandlerInterface $upsertHandler
     * @param CollectionProcessor $collectionProcessor
     * @param ItemsGenerator $itemsGenerator
     * @param SpecificationBuilderInterface $specificationBuilder
     * @param SerializerInterface $serializer
     * @param RetryManagerInterface $retryManager
     * @param ContextManagerInterface $contextManager
     */
    public function __construct(
        IndexingEntityProvider              $indexingEntityProvider,
        AthosCommerceLogger                 $logger,
        StoreManagerInterface               $storeManager,
        ConfigModel                         $config,
        UpdateIndexingEntitiesActionsAction $updateIndexingEntitiesActionsAction,
        DeleteEntityHandlerInterface        $deleteHandler,
        UpsertEntityHandlerInterface        $upsertHandler,
        CollectionProcessor                 $collectionProcessor,
        ItemsGenerator                      $itemsGenerator,
        SpecificationBuilderInterface       $specificationBuilder,
        SerializerInterface                 $serializer,
        RetryManagerInterface               $retryManager,
        ContextManagerInterface             $contextManager
    )
    {
        $this->indexingEntityProvider = $indexingEntityProvider;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->updateIndexingEntitiesActionsAction = $updateIndexingEntitiesActionsAction;
        $this->deleteHandler = $deleteHandler;
        $this->upsertHandler = $upsertHandler;
        $this->collectionProcessor = $collectionProcessor;
        $this->itemsGenerator = $itemsGenerator;
        $this->specificationBuilder = $specificationBuilder;
        $this->serializer = $serializer;
        $this->retryManager = $retryManager;
        $this->contextManager = $contextManager;
    }

    /**
     * @param $store
     * @param string $siteId
     *
     * @return int
     */
    public function execute($store, string $siteId): int
    {
        $storeId = (int)$store->getId();
        $storeCode = $store->getCode();

        $perMinute = $this->config->getRequestPerMinuteByStoreId($storeId);
        $payloadConfig = $this->config->getPayloadByStoreId($storeId);
        if (!$payloadConfig) {
            $this->logger->error('Missing payload config', ['store' => $storeCode]);

            return 0;
        }

        if (is_string($payloadConfig)) {
            $payloadConfig = $this->serializer->unserialize($payloadConfig);
        }

        if (!is_array($payloadConfig)) {
            $this->logger->error(
                'Invalid payload config type',
                [
                    'store' => $storeCode,
                    'payloadConfig' => $payloadConfig,
                    'getType' => gettype($payloadConfig),
                ]
            );

            return 0;
        }

        $feedSpecification = $this->specificationBuilder->build($payloadConfig);
        $feedSpecification->setIndexingMode(FeedSpecificationInterface::LIVE_MODE);
        $this->contextManager->setContextFromSpecification($feedSpecification);
        //This is to avoid rate limit at receiving end
        $maxLimit = (int)$perMinute * 2;

        $this->logger->info(
            sprintf(
                '[LiveIndexing] Initiated for store:%s | siteId:%s | requests:%s | maxLimit:%s',
                $storeCode,
                $siteId,
                $perMinute,
                $maxLimit
            ),
        );

        $deleteRecords = $this->indexingEntityProvider->get(
            null,
            [$siteId],
            null,
            Actions::DELETE,
            null,
            null,
            $maxLimit
        );

        $deleteCount = count($deleteRecords);

        $this->logger->info(
            sprintf(
                '[LiveIndexing] Delete IDs summary | Store: %s | Count: %s',
                $storeCode,
                $deleteCount
            ),
        );

        $skipUpdate = $deleteCount >= $maxLimit;
        $updateProductIds = [];

        if ($skipUpdate) {
            $this->logger->info(
                '[LiveIndexing] Skipping Update operation fully because deletes saturate window',
                [
                    'siteId' => $siteId,
                    'store' => $storeCode,
                    'deleteCount' => $deleteCount,
                    'maxLimit' => $maxLimit,
                ]
            );
        } else {
            $remainingRequests = (int)max(0, $maxLimit - $deleteCount);
            if ($remainingRequests > 0) {
                $this->logger->info(
                    '[LiveIndexing] Fetching indexable Update IDs',
                    [
                        'siteId' => $siteId,
                        'store' => $storeCode,
                        'remainingRequests' => $remainingRequests,
                    ]
                );

                $updateProductIds = $this->indexingEntityProvider->get(
                    null,
                    [$siteId],
                    null,
                    Actions::UPSERT,
                    true,
                    null,
                    $remainingRequests
                );
                $this->logger->info(
                    sprintf(
                        '[LiveIndexing] Update IDs summary | Store: %s | SiteId: %s | Count: %s',
                        $storeCode,
                        $siteId,
                        count($updateProductIds)
                    ),
                );
            }
        }

        $deleteIds = [];

        foreach ($deleteRecords as $deleteRecord) {
            if (method_exists($deleteRecord, 'getTargetId')) {
                $deleteIds[] = (int)$deleteRecord->getTargetId();
            }
        }

        $successDeleteIds = [];
        $failedDeleteIds = [];
        if (!empty($deleteIds)) {
            $this->logger->info(
                '[LiveIndexing] DELETE operation started',
                [
                    'siteId' => $siteId,
                    'store' => $storeCode,
                    'count' => count($deleteIds),
                ]
            );

            foreach ($deleteIds as $deleteId) {
                $deleteId = (int)$deleteId;
                try {
                    $deleteStatus = $this->deleteHandler->process($deleteId);
                    if ($deleteStatus) {
                        $successDeleteIds[] = $deleteId;
                    } else {
                        $failedDeleteIds[] = $deleteId;
                    }
                } catch (\Throwable $e) {
                    $failedDeleteIds[] = $deleteId;
                    $this->logger->error(
                        sprintf('Exception thrown while DELETION for ID(%s)', $deleteId),
                        [
                            'siteId' => $siteId,
                            'store' => $storeCode,
                            'error' => $e->getMessage(),
                        ]
                    );
                }
            }
            $this->logger->info(
                '[LiveIndexing] DELETE operation completed',
                [
                    'siteId' => $siteId,
                    'store' => $storeCode,
                    'successIds' => $successDeleteIds,
                    'failedIds' => $failedDeleteIds,
                ]
            );
        }
        $successDeleteMagentoEntityIds = array_values(array_unique($successDeleteIds));
        if (!empty($successDeleteMagentoEntityIds)) {
            $this->updateIndexingEntitiesActionsAction->execute(
                $successDeleteMagentoEntityIds,
                $siteId,
                Actions::DELETE,
                IndexingEntity::TARGET_ID
            );
            $this->logger->info(
                '[LiveIndexing][DELETE] Action updates completed successfully',
                [
                    'siteId' => $siteId,
                    'store' => $storeCode,
                    'successIds' => $successDeleteMagentoEntityIds,
                ]
            );
        }

        $updatePayloads = [];
        $magentoEntityIds = [];
        if (!empty($updateProductIds)) {
            $startTimestamp = microtime(true);
            $updateIds = [];
            foreach ($updateProductIds as $updateRecord) {
                if (method_exists($updateRecord, 'getTargetId')) {
                    $updateIds[$updateRecord->getId()] = (int)$updateRecord->getTargetId();
                }
            }

            $magentoEntityIds = array_values($updateIds);

            $collection = $this->collectionProcessor->getCollection($feedSpecification);
            $collection->addFieldToFilter('entity_id', ['in' => $magentoEntityIds]);
            $collection->setPageSize(min(count($magentoEntityIds), self::MAX_DB_FETCH));
            $collection->load();

            $this->collectionProcessor->processAfterLoad($collection, $feedSpecification);
            $this->logger->debug(
                '[LiveIndexing][Update][Collection Query]',
                [
                    'siteId' => $siteId,
                    'store' => $storeCode,
                    'query' => $collection->getSelect()->__toString(),
                    'timeTakenForCollection' => microtime(true) - $startTimestamp,
                ]
            );
            $this->itemsGenerator->resetDataProviders($feedSpecification);
            $startTimestamp = microtime(true);
            $items = $this->itemsGenerator->generate($collection->getItems(), $feedSpecification);
            $this->itemsGenerator->resetDataProvidersAfterFetchItems($feedSpecification);

            foreach ($items as $item) {
                $updatePayloads[] = $item;
            }
            $this->logger->debug(
                '[LiveIndexing][Update][ItemGeneration]',
                [
                    'siteId' => $siteId,
                    'store' => $storeCode,
                    'timeTakenForItemGeneration' => microtime(true) - $startTimestamp,
                ]
            );
        }

        $successUpdateIds = [];
        $failedUpdateIds = [];
        if (!empty($updatePayloads)) {
            $chunkSize = $this->config->getChunkSizeByStoreId($storeId);
            $delayMs = $this->config->getMillisecondsDelayByStoreId($storeId);
            $batchChunks = array_chunk($updatePayloads, $chunkSize);
            $totalChunks = count($batchChunks);
            foreach ($batchChunks as $chunkIndex => $chunk) {
                $this->logger->info(
                    sprintf(
                        '[LiveIndexing] UPDATE processing chunk %s of %s',
                        $chunkIndex + 1,
                        $totalChunks
                    ),
                    [
                        'siteId' => $siteId,
                        'store' => $storeCode,
                        'chunkSize' => count($chunk),
                    ]
                );
                foreach ($chunk as $updateProduct) {
                    if (!$updateProduct) {
                        continue;
                    }
                    try {
                        $id = $this->extractEntityId($updateProduct);
                        $singleOk = $this->upsertHandler->process($updateProduct);
                        if ($singleOk) {
                            $successUpdateIds[] = $id;
                        } else {
                            $failedUpdateIds[] = $id;
                        }
                    } catch (\Throwable $e) {
                        $id = $this->extractEntityId($updateProduct);
                        $failedUpdateIds[] = $id;

                        $this->logger->error(
                            sprintf('Exception thrown while UPDATE for ID(%s)', $id),
                            [
                                'siteId' => $siteId,
                                'store' => $storeCode,
                                'error' => $e->getMessage(),
                            ]
                        );
                    }
                }

                if ($delayMs > 0 && (($chunkIndex + 1) < $totalChunks)) {
                    // Introduce delay between each chunks to avoid rate limiting
                    // minimize delay to 1 second (1000 ms) max to avoid long sleeps
                    usleep(min($delayMs, 1000));
                }
            }

            $this->logger->info(
                '[LiveIndexing] UPDATE operation completed',
                [
                    'siteId' => $siteId,
                    'store' => $storeCode,
                    'chunkIndex' => $chunkIndex + 1,
                    'successIds' => $successUpdateIds,
                    'failedIds' => $failedUpdateIds,
                ]
            );
        }

        $successUpdateIds = array_values(array_unique($successUpdateIds));
        if (!empty($successUpdateIds)) {
            $this->updateIndexingEntitiesActionsAction->execute(
                $successUpdateIds,
                $siteId,
                Actions::UPSERT,
                IndexingEntity::TARGET_ID
            );
            $this->logger->info(
                '[LiveIndexing][UPDATE] Action updates completed successfully',
                [
                    'siteId' => $siteId,
                    'store' => $storeCode,
                    'successUpdateIds' => $successUpdateIds,
                ]
            );
        }

        $totalSuccessCount = count($successDeleteIds + $successUpdateIds);
        $this->logger->info(
            '[LiveIndexing] Summary',
            [
                'siteId' => $siteId,
                'store' => $storeCode,
                'totalSuccessCount' => $totalSuccessCount,
                'totalFailedCount' => count($failedDeleteIds + $failedUpdateIds),
            ]
        );
        $this->contextManager->resetContext();

        return $totalSuccessCount;
    }

    /**
     * @param $payload
     *
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
