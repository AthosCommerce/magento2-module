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

namespace AthosCommerce\Feed\Model;

use AthosCommerce\Feed\Api\EntityDiscoveryInterface;
use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Model\Api\MagentoEntityInterface;
use AthosCommerce\Feed\Model\Api\MagentoEntityInterfaceFactory;
use AthosCommerce\Feed\Model\Config as ConfigModel;
use AthosCommerce\Feed\Model\CollectionProcessor;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use AthosCommerce\Feed\Service\Action\AddIndexingEntitiesActionInterface;
use AthosCommerce\Feed\Service\Action\SetIndexingEntitiesToDeleteActionInterface;
use AthosCommerce\Feed\Service\Action\SetIndexingEntitiesToUpdateActionInterface;
use AthosCommerce\Feed\Service\Provider\Api\IndexingEntityProviderInterface;
use AthosCommerce\Feed\Service\Provider\MagentoEntityProvider;
use Exception;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\RelationsProvider as ProductRelationsProvider;

class EntityDiscovery implements EntityDiscoveryInterface
{
    private array $childParentCache = [];

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ConfigModel
     */
    private $configModel;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;
    /**
     * @var AddIndexingEntitiesActionInterface
     */
    private $addIndexingEntitiesAction;
    /**
     * @var MagentoEntityInterface
     */
    private $magentoEntityInterfaceFactory;
    /**
     * @var CollectionProcessor
     */
    private $collectionProcessor;
    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var ProductRelationsProvider
     */
    private $productRelationProvider;
    /**
     * @var MagentoEntityProvider
     */
    private $magentoEntityProvider;
    /**
     * @var IndexingEntityProviderInterface
     */
    private $indexingEntityProvider;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;
    private $connection;
    /**
     * @var SetIndexingEntitiesToDeleteActionInterface
     */
    private $setIndexingEntitiesToDeleteAction;
    /**
     * @var SetIndexingEntitiesToUpdateActionInterface
     */
    private $setIndexingEntitiesToUpdateAction;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Config $configModel
     * @param AthosCommerceLogger $logger
     * @param AddIndexingEntitiesActionInterface $addIndexingEntitiesAction
     * @param MagentoEntityInterfaceFactory $magentoEntityInterfaceFactory
     * @param CollectionProcessor $collectionProcessor
     * @param SpecificationBuilderInterface $specificationBuilder
     * @param SerializerInterface $serializer
     * @param ProductRelationsProvider $productRelationProvider
     * @param MagentoEntityProvider $magentoEntityProvider
     * @param IndexingEntityProviderInterface $indexingEntityProvider
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param SetIndexingEntitiesToDeleteActionInterface $setIndexingEntitiesToDeleteAction
     */
    public function __construct(
        StoreManagerInterface                      $storeManager,
        ConfigModel                                $configModel,
        AthosCommerceLogger                        $logger,
        AddIndexingEntitiesActionInterface         $addIndexingEntitiesAction,
        MagentoEntityInterfaceFactory              $magentoEntityInterfaceFactory,
        CollectionProcessor                        $collectionProcessor,
        SpecificationBuilderInterface              $specificationBuilder,
        SerializerInterface                        $serializer,
        ProductRelationsProvider                   $productRelationProvider,
        MagentoEntityProvider                      $magentoEntityProvider,
        IndexingEntityProviderInterface            $indexingEntityProvider,
        \Magento\Framework\App\ResourceConnection  $resource,
        SetIndexingEntitiesToDeleteActionInterface $setIndexingEntitiesToDeleteAction,
        SetIndexingEntitiesToUpdateActionInterface $setIndexingEntitiesToUpdateAction
    )
    {
        $this->storeManager = $storeManager;
        $this->configModel = $configModel;
        $this->logger = $logger;
        $this->addIndexingEntitiesAction = $addIndexingEntitiesAction;
        $this->magentoEntityInterfaceFactory = $magentoEntityInterfaceFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->specificationBuilder = $specificationBuilder;
        $this->serializer = $serializer;
        $this->productRelationProvider = $productRelationProvider;
        $this->magentoEntityProvider = $magentoEntityProvider;
        $this->indexingEntityProvider = $indexingEntityProvider;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->setIndexingEntitiesToDeleteAction = $setIndexingEntitiesToDeleteAction;
        $this->setIndexingEntitiesToUpdateAction = $setIndexingEntitiesToUpdateAction;
    }

    /**
     * @param array|null $storeCodes
     * @return array
     */
    public function execute(?array $storeCodes = null): array
    {
        $response = [];
        foreach ($this->resolveStores($storeCodes) as $store) {

            $storeId = (int)$store->getId();
            $storeCode = $store->getCode();

            $isValid = $this->validateLiveIndexingConfig($storeId);
            $siteId = $this->configModel->getSiteIdByStoreId($storeId);
            if ($isValid === false) {
                $this->logger->info(
                    "[Discovery] Configuration incomplete for store: " . $storeCode,
                    [
                        'endpoint' => $this->configModel->getEndpointByStoreId($storeId),
                        'status' => $this->configModel->isLiveIndexingEnabled($storeId),
                        'siteId' => $siteId,
                    ]
                );
                continue;
            }

            $payload = $this->configModel->getPayloadByStoreId($storeId);
            if (!$payload) {
                $this->logger->debug("[Discovery] Task Payload is not found for store: $storeId");
                continue;
            }
            if (is_string($payload)) {
                $payload = $this->serializer->unserialize($payload);
            }

            if (!is_array($payload)) {
                $this->logger->info(
                    "Invalid Task Payload type found for store: " . $store->getCode(),
                    [
                        'payload_type' => gettype($payload),//phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                        'payload_value' => $payload
                    ]
                );
                continue;
            }

            try {
                $this->logger->info(
                    "[Discovery] started for $storeCode/$siteId"
                );

                $this->discoverDeletions($siteId, $storeCode);
                //TODO:: check with observer if updates are needed
                //$this->discoverUpdates($siteId, $storeCode);
                $this->discoverAdditions($siteId, $storeCode, $payload);

                $this->logger->info(
                    "[Discovery] finished for $storeCode/$siteId"
                );

            } catch (Exception $e) {
                $this->logger->error(
                    "[Discovery] error for $storeCode/$siteId: " . $e->getMessage()
                );
            }
            $response[$storeId] = $storeCode;
        }
        return $response;
    }

    /**
     * @param array|null $storeCodes
     * @return \Generator
     */
    private function resolveStores(?array $storeCodes): \Generator
    {
        if ($storeCodes) {
            foreach ($storeCodes as $code) {
                try {
                    yield $this->storeManager->getStore($code);
                } catch (\Exception $e) {
                    $this->logger->warning("[Discovery] Invalid store code: {$code}");
                }
            }
            return;
        }

        foreach ($this->storeManager->getStores(false) as $store) {
            yield $store;
        }
    }

    /**
     * @param string $siteId
     * @return \Generator|int[]
     */
    public function getIndexedAthosIds(string $siteId): \Generator
    {
        $table = $this->resource->getTableName('athoscommerce_indexing_entity');
        $lastId = 0;

        do {
            $select = $this->connection->select()
                ->from($table, ['target_id'])
                ->where('target_entity_type = ?', Constants::PRODUCT_KEY)
                ->where('site_id = ?', $siteId)
                ->where('target_id > ?', $lastId)
                ->order('target_id ASC')
                ->limit(1000);

            $athosIds = $this->connection->fetchCol($select);
            if (!$athosIds) {
                break;
            }

            yield $athosIds;
            $lastId = (int)end($athosIds);

        } while (true);
    }

    /**
     * @param string $siteId
     * @param string $storeCode
     * @param array $payload
     * @return void
     */
    private function discoverAdditions(string $siteId, string $storeCode, array $payload): void
    {
        $feedSpecification = $this->specificationBuilder->build($payload);

        foreach ($this->magentoEntityProvider->getMagentoEntityIds($feedSpecification) as $magentoIds) {
            if (!is_array($magentoIds)) {
                throw new \LogicException(
                    'Expected batch of Magento IDs, got single ID'
                );
            }
            $indexedIds = $this->getExistingIndexedIds($magentoIds, $siteId);
            $entityIdsToAdd = array_diff($magentoIds, $indexedIds);
            if (!$entityIdsToAdd) {
                $this->logger->info(
                    "[Discovery] No ids found for ADD $storeCode: "
                );
                continue;
            }
            $this->logger->info(
                "[Discovery] ADD $storeCode: " . implode(',', $entityIdsToAdd)
            );

            $this->saveIdsToAthosEntities($entityIdsToAdd, $siteId);
        }
    }

    /**
     * Returns IDs that already exist in athoscommerce_indexing_entity
     *
     * @param int[] $targetIds Magento product IDs
     * @param string $siteId
     * @return int[] existing indexed IDs
     */
    private function getExistingIndexedIds(
        array  $targetIds,
        string $siteId
    ): array
    {
        if (empty($targetIds)) {
            return [];
        }

        $table = $this->resource->getTableName('athoscommerce_indexing_entity');
        $existing = [];

        //TODO:: Change chunk size if needed
        foreach (array_chunk($targetIds, 500) as $chunk) {

            $select = $this->connection->select()
                ->from($table, ['target_id'])
                ->where('target_entity_type = ?', Constants::PRODUCT_KEY)
                ->where('site_id = ?', $siteId)
                ->where('target_id IN (?)', $chunk);

            $rows = $this->connection->fetchCol($select);

            if ($rows) {
                // ensure ints, avoid duplicates
                foreach ($rows as $id) {
                    $existing[(int)$id] = (int)$id;
                }
            }
        }

        return array_values($existing);
    }

    /**
     * @param int[] $ids
     * @return int[] existing Magento Entity IDs
     */
    private function filterMagentoEntityIds(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        $table = $this->resource->getTableName('catalog_product_entity');
        $existingEntityIds = [];
        $chunkIds = array_chunk($ids, 500);
        foreach ($chunkIds as $chunk) {
            $select = $this->connection->select()
                ->from($table, ['entity_id'])
                ->where('entity_id IN (?)', $chunk);

            $existingEntityIds = array_merge(
                $existingEntityIds,
                $this->connection->fetchCol($select)
            );
        }

        return $existingEntityIds;
    }

    /**
     * @param string $siteId
     * @param string $storeCode
     * @return void
     */
    private function discoverDeletions(string $siteId, string $storeCode): void
    {
        foreach ($this->getIndexedAthosIds($siteId) as $athosIndexedIds) {

            $existingMagentoIds = $this->filterMagentoEntityIds($athosIndexedIds);
            if (empty($existingMagentoIds)) {
                $existingMagentoIds = [];
            }

            $idsToDelete = array_diff($athosIndexedIds, $existingMagentoIds);
            if (!$idsToDelete) {
                $this->logger->info(
                    "[Discovery] No ids found for DELETE $storeCode: "
                );
                continue;
            }

            $this->logger->info(
                "[Discovery] DELETE $storeCode: " . implode(',', $idsToDelete)
            );

            $this->setIndexingEntitiesToDeleteAction->execute($idsToDelete);
        }
    }

    /**
     * @param string $siteId
     * @param string $storeCode
     * @return void
     */
    private function discoverUpdates(string $siteId, string $storeCode): void
    {
        $tableProduct = $this->resource->getTableName('catalog_product_entity');
        $tableIndex = $this->resource->getTableName('athoscommerce_indexing_entity');

        //TODO:: Check against entity_id vs row_id pending
        $select = $this->connection->select()
            ->from(['p' => $tableProduct], ['entity_id'])
            ->join(
                ['i' => $tableIndex],
                'i.target_id = p.entity_id AND i.site_id = ' . $this->connection->quote($siteId),
                []
            )
            ->where('i.target_entity_type = ?', Constants::PRODUCT_KEY)
            ->where('p.updated_at > i.last_action_timestamp');

        $idsUpdate = $this->connection->fetchCol($select);

        if ($idsUpdate) {
            $this->setIndexingEntitiesToUpdateAction->execute($idsUpdate);
            $this->logger->info(
                "[Discovery] UPDATE $storeCode: " . implode(',', $idsUpdate)
            );
        } else {
            $this->logger->info(
                "[Discovery] No ids found for UPDATE $storeCode: "
            );
        }
    }

    /**
     * @param array $entityIds
     * @param string $siteId
     *
     * @return void
     */
    private function saveIdsToAthosEntities(
        array  $entityIds,
        string $siteId
    )
    {
        if (empty($entityIds)) {
            return;
        }

        $magentoEntities = [];
        $childToParentMap = [];
        foreach ($entityIds as $childId) {
            if (isset($this->childParentCache[$childId])) {
                $childToParentMap[$childId] = $this->childParentCache[$childId];
            }
        }

        $missingChildIds = array_diff($entityIds, array_keys($childToParentMap));
        if (!empty($missingChildIds)) {
            $configRelations = $this->productRelationProvider->getConfigurableRelationIds($missingChildIds);
            $groupedRelations = $this->productRelationProvider->getGroupRelationIds($missingChildIds);

            $relations = array_merge($configRelations, $groupedRelations);

            foreach ($relations as $relation) {
                $parentId = (int)$relation['parent_id'];
                $childId = (int)$relation['product_id'];

                $childToParentMap[$childId] = $parentId;
                $this->childParentCache[$childId] = $parentId;
            }
        }

        foreach ($entityIds as $id) {
            $magentoEntities[$id] = $this->magentoEntityInterfaceFactory->create([
                'entityId' => $id,
                'entityParentId' => $childToParentMap[$id] ?? 0,
                'siteId' => $siteId,
                'isIndexable' => false, //will set to indexable false, so let's the observer do the rest
            ]);
        }
        $this->addIndexingEntitiesAction->execute(
            Constants::PRODUCT_KEY,
            $magentoEntities
        );
    }
    /**
     * @param int $storeId
     *
     * @return bool
     */
    private function validateLiveIndexingConfig(int $storeId): bool
    {
        return $this->configModel->getEndpointByStoreId($storeId)
            && $this->configModel->isLiveIndexingEnabled($storeId)
            && $this->configModel->getSiteIdByStoreId($storeId);
    }
}
