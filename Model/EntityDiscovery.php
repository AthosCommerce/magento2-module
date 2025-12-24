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

namespace AthosCommerce\Feed\Model;

use AthosCommerce\Feed\Api\EntityDiscoveryInterface;
use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Model\Api\MagentoEntityInterface;
use AthosCommerce\Feed\Model\Api\MagentoEntityInterfaceFactory;
use AthosCommerce\Feed\Model\Config as ConfigModel;
use AthosCommerce\Feed\Model\CollectionProcessor;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use AthosCommerce\Feed\Service\Action\AddIndexingEntitiesActionInterface;
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
     * @param StoreManagerInterface $storeManager
     * @param Config $configModel
     * @param AthosCommerceLogger $logger
     * @param AddIndexingEntitiesActionInterface $addIndexingEntitiesAction
     * @param MagentoEntityInterfaceFactory $magentoEntityInterfaceFactory
     * @param \AthosCommerce\Feed\Model\CollectionProcessor $collectionProcessor
     * @param SpecificationBuilderInterface $specificationBuilder
     * @param SerializerInterface $serializer
     * @param ProductRelationsProvider $productRelationProvider
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ConfigModel $configModel,
        AthosCommerceLogger $logger,
        AddIndexingEntitiesActionInterface $addIndexingEntitiesAction,
        MagentoEntityInterfaceFactory $magentoEntityInterfaceFactory,
        CollectionProcessor $collectionProcessor,
        SpecificationBuilderInterface $specificationBuilder,
        SerializerInterface $serializer,
        ProductRelationsProvider $productRelationProvider,
    ) {
        $this->storeManager = $storeManager;
        $this->configModel = $configModel;
        $this->logger = $logger;
        $this->addIndexingEntitiesAction = $addIndexingEntitiesAction;
        $this->magentoEntityInterfaceFactory = $magentoEntityInterfaceFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->specificationBuilder = $specificationBuilder;
        $this->serializer = $serializer;
        $this->productRelationProvider = $productRelationProvider;
    }

    /**
     * @param array|null $storeCodes
     *
     * @return array
     */
    public function execute(?array $storeCodes): array
    {
        $storesToProcess = [];
        $response = [];

        if (!empty($storeCodes)) {
            foreach ($storeCodes as $code) {
                try {
                    $store = $this->storeManager->getStore($code);
                    $storesToProcess[] = $store;
                } catch (\Exception $e) {
                    $this->logger->info("Store code not found: {$code}");
                }
            }
        } else {
            $storesToProcess = $this->storeManager->getStores(false);
        }

        foreach ($storesToProcess as $store) {
            if (!$store) {
                continue;
            }
            $storeId = (int)$store->getId();

            $isEnabled = $this->configModel->isLiveIndexingEnabled($storeId);
            $siteId = $this->configModel->getSiteIdByStoreId($storeId);
            $payload = $this->configModel->getPayloadByStoreId($storeId);

            if (!$isEnabled || !$siteId || !$payload) {
                $this->logger->info(
                    "Live indexing is disabled or Site Id or taskPayload not found for store: " . $store->getCode()
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
            // Set page size for processing
            $pageSize = 10000;
            $collection = $this->collectionProcessor->getCollection($feedSpecification);
            $collection->setPageSize($pageSize);
            $pageCount = $collection->getLastPageNumber();
            $currentPageNumber = 1;
            $errorCount = 0;
            while ($currentPageNumber <= $pageCount) {
                try {
                    $collection->setCurPage($currentPageNumber);
                    $excludeIds = $feedSpecification->getExcludedProductIds();
                    if (!empty($excludeIds)) {
                        $collection->addFieldToFilter('entity_id', ['nin' => $excludeIds]);
                        $this->logger->info('Entity discovery processed for only not excluded products', [
                            'method' => __METHOD__,
                            'loadedIDs' => $collection->getLoadedIds(),
                            'excludedProductIds' => $excludeIds,
                        ]);
                    }
                    $collection->load();
                    $items = $collection->getItems();
                    if ($items) {
                        $this->convertCollectionItemsToAthosEntities($items, $siteId);
                    }
                    $this->logger->info(
                        sprintf(
                            "Entity discovery processed page (%d) of (%d) for store: %s",
                            $currentPageNumber,
                            $pageCount,
                            $store->getCode()
                        ),
                        [
                            'query' => $collection->getSelect()->__toString(),
                        ]
                    );
                    $currentPageNumber++;
                } catch (Exception $exception) {
                    if ($errorCount == 3) {
                        $this->logger->error(
                            $exception->getMessage(),
                            [
                                'method' => __METHOD__,
                                'trace' => $exception->getTraceAsString(),
                            ]
                        );
                        break;
                    }
                    $errorCount++;
                    continue;
                }
            }
            $response[$storeId] = $store->getCode();
        }

        return $response;
    }

    /**
     * @param array $items
     * @param string $siteId
     *
     * @return void
     */
    private function convertCollectionItemsToAthosEntities(
        array $items,
        string $siteId
    ) {
        $magentoEntities = [];
        $childIds = array_map(fn($item) => $item->getId(), $items);

        $childToParentMap = [];
        foreach ($childIds as $childId) {
            if (isset($this->childParentCache[$childId])) {
                $childToParentMap[$childId] = $this->childParentCache[$childId];
            }
        }


        $missingChildIds = array_diff($childIds, array_keys($childToParentMap));
        if (!empty($missingChildIds)) {
            $configRelations = $this->productRelationProvider->getConfigurableRelationIds($missingChildIds);
            $groupedRelations = $this->productRelationProvider->getGroupRelationIds($missingChildIds);

            $relations = array_merge($configRelations, $groupedRelations);

            foreach ($relations as $relation) {
                $parentId = (int)$relation['parent_id'];
                $childId = $relation['product_id'];
                $childToParentMap[$childId] = $parentId;
                $this->childParentCache[$childId] = $parentId;
            }
        }

        foreach ($items as $item) {
            $parentId = $childToParentMap[$item->getId()] ?? 0;
            $magentoEntities[$item->getId()] = $this->magentoEntityInterfaceFactory->create([
                'entityId' => $item->getId(),
                'entitySubtype' => $item->getTypeId() ?? 'simple',
                'entityParentId' => $parentId,
                'siteId' => $siteId,
                'isIndexable' => false, //will set to indexable false, so let's the observer do the rest
            ]);
        }
        $this->addIndexingEntitiesAction->execute(
            Constants::PRODUCT_KEY,
            $magentoEntities
        );
    }
}
