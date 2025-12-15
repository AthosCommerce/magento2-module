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

namespace AthosCommerce\Feed\Observer\Product;

use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Model\Source\Actions;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use AthosCommerce\Feed\Observer\BaseProductObserver;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResourceConnection;

class BunchSaveObserver implements ObserverInterface
{
    /**
     * @var BaseProductObserver
     */
    private $baseProductObserver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @param BaseProductObserver $baseProductObserver
     * @param LoggerInterface $logger
     * @param ResourceConnection $resourceConnection
     * @param ProductRepositoryInterface $productRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductResource $productResource
     */
    public function __construct(
        BaseProductObserver        $baseProductObserver,
        LoggerInterface            $logger,
        ResourceConnection         $resourceConnection,
        ProductRepositoryInterface $productRepository,
        ScopeConfigInterface       $scopeConfig,
        ProductResource            $productResource,
    )
    {
        $this->baseProductObserver = $baseProductObserver;
        $this->logger = $logger;
        $this->resource = $resourceConnection;
        $this->productRepository = $productRepository;
        $this->scopeConfig = $scopeConfig;
        $this->productResource = $productResource;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $event = $observer->getEvent();
            $bunch = (array)$event->getBunch();

            if (empty($bunch)) {
                $this->logger->debug('[BunchSaveObserver] Bunch is empty.');
                return;
            }

            $skus = array_column($bunch, 'sku');
            if(empty($skus)) {
                $this->logger->debug('[BunchSaveObserver] No SKUs found in bunch.');
                return;
            }

            // Fetch entity_ids for the SKUs in one query
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('catalog_product_entity');
            $select = $connection->select()
                ->from($table, ['sku', 'entity_id'])
                ->where('sku IN (?)', $skus);
            $skuToData = $connection->fetchAll($select);

            if (empty($skuToData)) {
                $this->logger->info('[BunchSaveObserver] No matching products found.');
                return;
            }

            $productIds = array_column($skuToData, 'entity_id');
            $productIdsToProcess = [];

            // Process in chunks to avoid memory issues
            $chunks = array_chunk($productIds, 200);
            foreach ($chunks as $chunk) {
                try {
                    // Get store IDs for this chunk in ONE query
                    $productStores = $this->getStoreIdsForProducts($chunk);

                    foreach ($productStores as $productId => $storeIds) {
                        $shouldProcess = false;

                        foreach ($storeIds as $storeId) {
                            try {
                                $liveIndexing = (bool)$this->scopeConfig->getValue(
                                    Constants::XML_PATH_LIVE_INDEXING_ENABLED,
                                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                                    $storeId
                                );

                                if (!$liveIndexing) {
                                    continue;
                                }
                                $shouldProcess = true;
                            } catch (\Throwable $storeEx) {
                                $this->logger->error(
                                    "[BunchSaveObserver] Error for product {$productId}, store {$storeId}: " . $storeEx->getMessage(),
                                    ['trace' => $storeEx->getTraceAsString()]
                                );
                            }
                        }

                        if ($shouldProcess) {
                            $productIdsToProcess[] = $productId;
                        }
                    }

                } catch (\Throwable $chunkEx) {
                    $this->logger->error(
                        "[BunchSaveObserver] Chunk processing error: " . $chunkEx->getMessage(),
                        ['trace' => $chunkEx->getTraceAsString()]
                    );
                }
            }

            if (!empty($productIdsToProcess)) {
                $this->baseProductObserver->execute($productIdsToProcess, Actions::UPSERT);
                $this->logger->info(
                    '[BunchSaveObserver] executed for products',
                    ['productIds' => $productIdsToProcess]
                );
            }
        } catch (\Throwable $e) {
            $this->logger->critical(
                '[BunchSaveObserver] General error: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]
            );
        }
    }

    /**
     * Fetch store IDs for multiple products in one query
     */
    private function getStoreIdsForProducts(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $connection = $this->productResource->getConnection();
        $productWebsiteTable = $this->productResource->getTable('catalog_product_website');
        $storeTable = $this->productResource->getTable('store');

        $select = $connection->select()
            ->from(['pw' => $productWebsiteTable], ['product_id'])
            ->join(
                ['s' => $storeTable],
                's.website_id = pw.website_id',
                ['store_id']
            )
            ->where('pw.product_id IN (?)', $productIds);

        $rows = $connection->fetchAll($select);

        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['product_id']][] = (int)$row['store_id'];
        }

        return $result;
    }

}
