<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Model\LiveIndexing;

use AthosCommerce\Feed\Api\LiveIndexing\DeleteEntityHandlerInterface;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Service\Provider\IndexingEntityProvider as IndexingEntityProvider;
use AthosCommerce\Feed\Api\LiveIndexing\UpsertEntityHandlerInterface;
use AthosCommerce\Feed\Api\RetryManagerInterface;
use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Model\CollectionProcessor;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use AthosCommerce\Feed\Model\ItemsGenerator;
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Service\Action\UpdateIndexingEntitiesActionsActionInterface as UpdateIndexingEntitiesActionsAction;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Processor
{
    private const MAX_DB_FETCH = 960;

    /**
     * @var IndexingEntityProvider
     */
    private $indexingEntityProvider;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
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
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
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
        IndexingEntityProvider $indexingEntityProvider,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        UpdateIndexingEntitiesActionsAction $updateIndexingEntitiesActionsAction,
        DeleteEntityHandlerInterface $deleteHandler,
        UpsertEntityHandlerInterface $upsertHandler,
        CollectionProcessor $collectionProcessor,
        ItemsGenerator $itemsGenerator,
        SpecificationBuilderInterface $specificationBuilder,
        SerializerInterface $serializer,
        RetryManagerInterface $retryManager,
        ContextManagerInterface $contextManager
    ) {
        $this->indexingEntityProvider = $indexingEntityProvider;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
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
     * @param string|null $siteId
     *
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute($store, ?string $siteId = null): int
    {
        $storeId = (int)$store->getId();
        $storeCode = $store->getCode();

        $perMinute = (int)$this->scopeConfig->getValue(
            Constants::XML_PATH_LIVE_INDEXING_PER_MINUTE,
            ScopeInterface::SCOPE_STORES,
            $storeId
        )
            ?: Constants::DEFAULT_MAX_BATCH_LIMIT;

        //This is to avoid rate limit at receiving end
        $maxLimit = (int)$perMinute * 2;

        $this->logger->info(
            sprintf(
                '[LiveIndexing] starting for store(%s), siteId(%s), perMinutes(%s)',
                $siteId,
                $storeCode,
                $perMinute
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
                '[LiveIndexing] Total delete records(%s) found for store(%s).',
                $deleteCount,
                $storeCode
            ),
        );

        $skipUpsert = $deleteCount >= $maxLimit;
        $upsertProductIds = [];

        if ($skipUpsert) {
            $this->logger->info(
                '[LiveIndexing] Skipping Update operation fully because deletes saturate window',
                [
                    'store' => $storeCode,
                    'deleteCount' => $deleteCount,
                    'maxLimit' => $maxLimit,
                ]
            );
        } else {
            $remainingRequests = (int)max(0, $maxLimit - $deleteCount);
            if ($remainingRequests > 0) {
                $this->logger->info('[LiveIndexing] fetching update ids.', [
                    'store' => $storeCode,
                    'remainingRequests' => $remainingRequests,
                ]);

                $upsertProductIds = $this->indexingEntityProvider->get(
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
                        '[LiveIndexing] Total update records(%s) found for store(%s).',
                        count($upsertProductIds),
                        $storeCode
                    ),
                );
            }
        }

        $deleteIds = [];
        foreach ($deleteRecords as $deleteRecord) {
            if (method_exists($deleteRecord, 'getEntityId')) {
                $deleteIds[] = (int)$deleteRecord->getEntityId();
            } elseif (method_exists($deleteRecord, 'getId')) {
                $deleteIds[] = (int)$deleteRecord->getId();
            }
        }

        $successDeleteIds = [];
        $failedDeleteIds = [];
        if (!empty($deleteIds)) {
            $this->logger->info('[LiveIndexing] DELETE operation started',
                ['store' => $storeCode, 'count' => count($deleteIds)]
            );

            foreach ($deleteIds as $id) {
                try {
                    $deleteStatus = $this->deleteHandler->process($id);
                    if ($deleteStatus) {
                        //$this->retryManager->resetRetry($id, Actions::DELETE);
                        $successDeleteIds[] = $id;
                    } else {
                        $failedDeleteIds[] = $id;
                        /*$this->retryManager->markForRetry(
                            $id,
                            Actions::DELETE,
                            'handler.process returned false'
                        );*/
                    }
                } catch (\Throwable $e) {
                    $failedDeleteIds[] = $id;
                    //$this->retryManager->markForRetry($id, Actions::DELETE, $e->getMessage());

                    $this->logger->error(
                        sprintf('Exception thrown while DELETION for ID(%s)', $id),
                        ['store' => $storeCode, 'error' => $e->getMessage()]
                    );
                }
            }
            $this->logger->info('[LiveIndexing] DELETE operation completed',
                [
                    'store' => $storeCode,
                    'successIds' => $successDeleteIds,
                    'failedIds' => $failedDeleteIds,
                ]
            );
        }

        $upsertPayloads = [];
        if (!empty($upsertProductIds)) {
            $entityIds = array_map(fn($row) => (int)$row->getEntityId(), $upsertProductIds);

            $payloadConfig = $this->scopeConfig->getValue(
                Constants::XML_PATH_LIVE_INDEXING_TASK_PAYLOAD,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );

            if (!$payloadConfig) {
                $this->logger->error('Missing payload config', ['store' => $storeCode]);

                return 0;
            }

            if (is_string($payloadConfig)) {
                $payloadConfig = $this->serializer->unserialize($payloadConfig);
            }

            if (!is_array($payloadConfig)) {
                $this->logger->error('Invalid payload config type', ['store' => $storeCode]);

                return 0;
            }

            $feedSpecification = $this->specificationBuilder->build($payloadConfig);
            $collection = $this->collectionProcessor->getCollection($feedSpecification);
            $collection->addFieldToFilter('entity_id', ['in' => $entityIds]);
            $collection->setPageSize(min(count($entityIds), self::MAX_DB_FETCH));
            $collection->load();

            $this->collectionProcessor->processAfterLoad($collection, $feedSpecification);
            $this->logger->debug(
                '[Live Indexing][Collection Query]',
                [
                    'store' => $storeCode,
                    'query' => $collection->getSelect()->__toString(),
                ]
            );
            $this->itemsGenerator->resetDataProviders($feedSpecification);
            $items = $this->itemsGenerator->generate($collection->getItems(), $feedSpecification);
            $this->itemsGenerator->resetDataProvidersAfterFetchItems($feedSpecification);
            $this->contextManager->setContextFromSpecification($feedSpecification);
            foreach ($items as $item) {
                $upsertPayloads[] = $item;
            }
        }

        $successUpdateIds = [];
        $failedUpdateIds = [];
        if (!empty($upsertPayloads)) {
            $this->logger->info('[LiveIndexing] UPDATE Operation started.',
                ['store' => $storeCode, 'count' => count($upsertPayloads)]
            );

            foreach ($upsertPayloads as $updateProduct) {
                try {
                    $singleOk = $this->upsertHandler->process($updateProduct);
                    $id = $this->extractEntityId($updateProduct);
                    if ($singleOk) {
                        //$this->retryManager->resetRetry($id, Actions::UPSERT);
                        $successUpdateIds[] = $id;
                    } else {
                        $failedUpdateIds[] = $id;
                        //$this->retryManager->markForRetry($id, Actions::UPSERT, 'handler.process returned false');
                    }
                } catch (\Throwable $e) {
                    $id = $this->extractEntityId($updateProduct);
                    $failedUpdateIds[] = $id;
                    //$this->retryManager->markForRetry($id, Actions::UPSERT, $e->getMessage());

                    $this->logger->error(
                        sprintf('Exception thrown while UPDATE for ID(%s)', $id),
                        ['store' => $storeCode, 'error' => $e->getMessage()]
                    );
                }
            }
            $this->logger->info('[LiveIndexing] UPDATE operation completed',
                [
                    'store' => $storeCode,
                    'successIds' => $successUpdateIds,
                    'failedIds' => $failedUpdateIds,
                ]
            );
        }

        $successDeleteIds = array_values(array_unique($successDeleteIds));
        if (!empty($successDeleteIds)) {
            $this->updateIndexingEntitiesActionsAction->execute(
                $successDeleteIds,
                Actions::DELETE
            );
        }
        $this->logger->info('[LiveIndexing] DELETE next action completed',
            [
                'store' => $storeCode,
                'successIds' => $successDeleteIds,
            ]
        );

        $successUpdateIds = array_values(array_unique($successUpdateIds));
        if (!empty($successUpdateIds)) {
            $this->updateIndexingEntitiesActionsAction->execute(
                $successUpdateIds,
                Actions::UPSERT
            );
        }
        $this->logger->info('[LiveIndexing] UPDATE next action completed',
            [
                'store' => $storeCode,
                'successUpdateIds' => $successUpdateIds,
            ]
        );
        $totalSuccessCount = count($successDeleteIds + $successUpdateIds);
        $this->logger->info('[LiveIndexing] completed.',
            [
                'siteId' => $siteId,
                'store' => $storeCode,
                'totalSuccessCount' => $totalSuccessCount,
                'totalFailedCount' => count($failedDeleteIds + $failedUpdateIds),
            ]
        );

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
