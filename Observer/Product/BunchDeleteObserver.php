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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use AthosCommerce\Feed\Observer\BaseProductObserver;
use Psr\Log\LoggerInterface;
use \Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Store\Model\ResourceModel\Store as StoreResource;

class BunchDeleteObserver implements ObserverInterface
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
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var StoreResource
     */
    private $storeResource;

    /**
     * @param BaseProductObserver $baseProductObserver
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductResource $productResource
     * @param StoreResource $storeResource
     */
    public function __construct(
        BaseProductObserver        $baseProductObserver,
        LoggerInterface            $logger,
        ScopeConfigInterface       $scopeConfig,
        ProductResource            $productResource,
        StoreResource              $storeResource,
    )
    {
        $this->baseProductObserver = $baseProductObserver;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->productResource = $productResource;
        $this->storeResource = $storeResource;
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
            $productIdsToDelete = (array)$event->getIdsToDelete();

            if (empty($productIdsToDelete)) {
                $this->logger->debug('[BundleDeleteObserver] No products to delete.');
                return;
            }

            $productIdsToProcess = [];

            $chunks = array_chunk($productIdsToDelete, 100);

            foreach ($chunks as $productIdChunk) {
                try {
                    $productStores = $this->getStoreIdsForProducts($productIdChunk);

                    foreach ($productIdChunk as $productId) {
                        $storeIds = $productStores[$productId] ?? [];

                        if (empty($storeIds)) {
                            $this->logger->warning(
                                "[BundleDeleteObserver] No store IDs found for product ID {$productId}"
                            );
                            continue;
                        }

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

                                $this->logger->debug(
                                    '[BundleDeleteObserver] Store check passed',
                                    [
                                        'productId' => $productId,
                                        'storeId' => $storeId
                                    ]
                                );

                            } catch (\Throwable $storeEx) {
                                $this->logger->error(
                                    "[BundleDeleteObserver] Error processing product {$productId} for store {$storeId}: "
                                    . $storeEx->getMessage(),
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
                        "[BundleDeleteObserver] Chunk processing error: " . $chunkEx->getMessage(),
                        ['trace' => $chunkEx->getTraceAsString()]
                    );
                }
            }

            if (!empty($productIdsToProcess)) {
                $this->baseProductObserver->execute($productIdsToProcess, Actions::DELETE);

                $this->logger->info(
                    '[BundleDeleteObserver] executed for products',
                    ['productIds' => $productIdsToProcess]
                );
            } else {
                $this->logger->info('[BundleDeleteObserver] No products to process.');
            }

        } catch (\Throwable $e) {
            $this->logger->critical(
                '[BundleDeleteObserver] general error: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]
            );
        }
    }

    /**
     *
     * @param int[] $productIds
     * @return int[][]
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
