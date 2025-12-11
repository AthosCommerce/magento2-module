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
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use AthosCommerce\Feed\Service\Action\AddIndexingEntitiesActionInterface;
use AthosCommerce\Feed\Model\Api\MagentoEntityInterface;
use AthosCommerce\Feed\Model\Api\MagentoEntityInterfaceFactory;
use AthosCommerce\Feed\Model\CollectionProcessor;
use AthosCommerce\Feed\Helper\Constants;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

class EntityDiscovery implements EntityDiscoveryInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var LoggerInterface
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
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param AddIndexingEntitiesActionInterface $addIndexingEntitiesAction
     * @param MagentoEntityInterfaceFactory $magentoEntityInterfaceFactory
     * @param \AthosCommerce\Feed\Model\CollectionProcessor $collectionProcessor
     * @param SpecificationBuilderInterface $specificationBuilder
     * @param SerializerInterface $serializer
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        AddIndexingEntitiesActionInterface $addIndexingEntitiesAction,
        MagentoEntityInterfaceFactory $magentoEntityInterfaceFactory,
        CollectionProcessor $collectionProcessor,
        SpecificationBuilderInterface $specificationBuilder,
        SerializerInterface $serializer
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->addIndexingEntitiesAction = $addIndexingEntitiesAction;
        $this->magentoEntityInterfaceFactory = $magentoEntityInterfaceFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->specificationBuilder = $specificationBuilder;
        $this->serializer = $serializer;
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
            $storeId = $store->getId();

            $isEnabled = (bool)$this->scopeConfig->getValue(
                Constants::XML_PATH_LIVE_INDEXING_ENABLED,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
            if (!$isEnabled) {
                $this->logger->info(
                    "Found indexing disabled   for store: " . $store->getCode()
                );
                continue;
            }
            $siteId = (string)$this->scopeConfig->getValue(
                Constants::XML_PATH_CONFIG_SITE_ID,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
            if (!$siteId) {
                $this->logger->info(
                    "Found  site id not found for store: " . $store->getCode()
                );
                continue;
            }
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
            $pageSize = 1000;
            $collection = $this->collectionProcessor->getCollection($feedSpecification);
            $collection->setPageSize($pageSize);
            $pageCount = $collection->getLastPageNumber();
            $currentPageNumber = 1;
            $errorCount = 0;
            while ($currentPageNumber <= $pageCount) {
                try {
                    $collection->setCurPage($currentPageNumber);
                    $collection->load();
                    $items = $collection->getItems();
                    if ($items) {
                        $this->convertCollectionItemsToAthosEntities($items, $siteId);
                    }
                    $this->logger->info(
                        sprintf(
                            "Entity discovery processed page %d of %d for store: %s",
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
            $response[$storeId] = sprintf(
                "Entity discovery ran successfully for store: (%s)",
                $store->getCode()
            );
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
        foreach ($items as $item) {
            $magentoEntities[$item->getId()] = $this->magentoEntityInterfaceFactory->create([
                'entityId' => $item->getId(),
                'targetEntitySubtype' => $item->getDataUsingMethod('type_id'),
                'entityParentId' => 0,
                'siteId' => $siteId,
                'isIndexable' => false, //will set to indexable false, so let's the observer do the rest
            ]);
        }
        //TODO::
        $this->addIndexingEntitiesAction->execute(
            Constants::PRODUCT_KEY,
            $magentoEntities
        );
    }
}
