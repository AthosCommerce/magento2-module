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

use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Model\CollectionProcessor;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use AthosCommerce\Feed\Model\ItemsGenerator;
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Service\Provider\Api\IndexingEntityProviderInterface as IndexingEntityProvider;
use AthosCommerce\Feed\Service\Action\UpdateIndexingEntitiesActionsActionInterface as UpdateIndexingEntitiesActionsAction;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Processor
{
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
     * @var DeleteEntityHandler
     */
    private $deleteEntityHandler;
    /**
     * @var UpsertEntityHandler
     */
    private $upsertEntityHandler;
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
     * @param IndexingEntityProvider $indexingEntityProvider
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param UpdateIndexingEntitiesActionsAction $updateIndexingEntitiesActionsAction
     * @param DeleteEntityHandler $deleteEntityHandler
     * @param UpsertEntityHandler $upsertEntityHandler
     * @param CollectionProcessor $collectionProcessor
     * @param ItemsGenerator $itemsGenerator
     * @param SpecificationBuilderInterface $specificationBuilder
     * @param SerializerInterface $serializer
     */
    public function __construct(
        IndexingEntityProvider $indexingEntityProvider,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        UpdateIndexingEntitiesActionsAction $updateIndexingEntitiesActionsAction,
        DeleteEntityHandler $deleteEntityHandler,
        UpsertEntityHandler $upsertEntityHandler,
        CollectionProcessor $collectionProcessor,
        ItemsGenerator $itemsGenerator,
        SpecificationBuilderInterface $specificationBuilder,
        SerializerInterface $serializer
    ) {
        $this->indexingEntityProvider = $indexingEntityProvider;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->updateIndexingEntitiesActionsAction = $updateIndexingEntitiesActionsAction;
        $this->deleteEntityHandler = $deleteEntityHandler;
        $this->upsertEntityHandler = $upsertEntityHandler;
        $this->collectionProcessor = $collectionProcessor;
        $this->itemsGenerator = $itemsGenerator;
        $this->specificationBuilder = $specificationBuilder;
        $this->serializer = $serializer;
    }

    /**
     * @param int $limit
     * @param string|null $siteId
     *
     * @return int
     */
    public function execute(
        int $limit,
        ?string $siteId = null
    ): int {
        $total = 0;
        $operations = [
            Actions::DELETE => $this->deleteEntityHandler,
            Actions::UPSERT => $this->upsertEntityHandler,
        ];
        $storeId = $this->storeManager->getStore();
        foreach ($operations as $action => $handler) {
            $this->logger->info(sprintf('[%s] Operation started ', $action));
            $entities = $this->indexingEntityProvider->get(
                null,
                [$siteId],
                null,
                $action,
                null,
                null,
                $limit
            );

            if (!$entities) {
                continue;
            }

            $success = [];
            $fail = [];

            if ($action === Actions::UPSERT) {
                $entityIds = array_map(
                    static fn($entityRow) => (int)$entityRow->getEntityId(),
                    $entities
                );
                $payload = $this->scopeConfig->getValue(
                    Constants::XML_PATH_LIVE_INDEXING_TASK_PAYLOAD,
                    ScopeInterface::SCOPE_STORES,
                    $storeId
                );
                if (!$payload) {
                    $this->logger->info(
                        "Payload not found for store: " . $store->getCode()
                    );
                    continue;
                }
                if (is_string($payload)) {
                    $payload = $this->serializer->unserialize($payload);
                }
                if (!is_array($payload)) {
                    $this->logger->info(
                        "Invalid payload type found for store: " . $store->getCode()
                    );
                    continue;
                }
                $feedSpecification = $this->specificationBuilder->build($payload);
                $productCollection = $this->collectionProcessor->getCollection($feedSpecification);
                $productCollection->addFieldToFilter('entity_id', ['in' => $entityIds]);
                $productCollection->load();
                $this->collectionProcessor->processAfterLoad($productCollection, $feedSpecification);
                $items = $productCollection->getItems();

                $this->itemsGenerator->resetDataProviders($feedSpecification);
                $itemsData = $this->itemsGenerator->generate($items, $feedSpecification);
                $this->itemsGenerator->resetDataProvidersAfterFetchItems($feedSpecification);
                foreach ($itemsData as $row) {
                    $res = $handler->process($row);
                    if ($res) {
                        $success[] = $row['entity_id'];
                    } else {
                        $fail[] = $row['entity_id'];
                    }
                }
            } else {
                foreach ($entities as $row) {
                    $res = $handler->process($row);
                    if ($res) {
                        $success[] = $row->getId();
                    } else {
                        $fail[] = $row->getId();
                    }
                }
            }

            if (!empty($success)) {
                $this->updateIndexingEntitiesActionsAction->execute($success, $action);
            }
            $this->logger->debug(
                "API Status",
                [
                    'operation' => $action,
                    'successIds' => $success,
                    'failureIds' => $fail,
                ]
            );

            $total += count($success);
        }

        return $total;
    }
}
