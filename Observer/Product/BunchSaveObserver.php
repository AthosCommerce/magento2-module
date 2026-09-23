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
use AthosCommerce\Feed\Model\Config as ConfigModel;
use AthosCommerce\Feed\Model\Source\Actions;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use AthosCommerce\Feed\Observer\BaseProductObserver;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use Magento\Framework\App\ResourceConnection;
use AthosCommerce\Feed\Service\Provider\ProductNextActionProvider;

class BunchSaveObserver implements ObserverInterface
{
    private const UNSCOPED_SITE_KEY = '__all__';

    /**
     * @var BaseProductObserver
     */
    private $baseProductObserver;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ProductResource
     */
    private $productResource;
    /**
     * @var ProductNextActionProvider
     */
    private $productNextActionProvider;

    /**
     * @var ConfigModel
     */
    private $configModel;

    /**
     * @param BaseProductObserver $baseProductObserver
     * @param AthosCommerceLogger $logger
     * @param ResourceConnection $resourceConnection
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductResource $productResource
     * @param ProductNextActionProvider $productNextActionProvider
     * @param ConfigModel $configModel
     */
    public function __construct(
        BaseProductObserver       $baseProductObserver,
        AthosCommerceLogger       $logger,
        ResourceConnection        $resourceConnection,
        ScopeConfigInterface      $scopeConfig,
        ProductResource           $productResource,
        ProductNextActionProvider $productNextActionProvider,
        ConfigModel               $configModel
    )
    {
        $this->baseProductObserver = $baseProductObserver;
        $this->logger = $logger;
        $this->resource = $resourceConnection;
        $this->scopeConfig = $scopeConfig;
        $this->productResource = $productResource;
        $this->productNextActionProvider = $productNextActionProvider;
        $this->configModel = $configModel;
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
            if (empty($skus)) {
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
            $resolvedActionsBySite = [];

            // Process in chunks to avoid memory issues
            $chunks = array_chunk($productIds, 200);
            foreach ($chunks as $chunk) {
                try {
                    // Get store IDs for this chunk in one query and resolve actions per store.
                    $productStores = $this->getStoreIdsForProducts($chunk);
                    $productIdsByStore = [];

                    foreach ($productStores as $productId => $storeIds) {
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
                                $productIdsByStore[(int)$storeId][] = (int)$productId;
                            } catch (\Throwable $storeEx) {
                                $this->logger->error(
                                    "[BunchSaveObserver] Error: " . $storeEx->getMessage(),
                                    [
                                        'trace' => $storeEx->getTraceAsString(),
                                        'product_id' => $productId,
                                        'store_id' => $storeId
                                    ]
                                );
                            }
                        }
                    }

                    foreach ($productIdsByStore as $storeId => $productIdsForStore) {
                        $productIdsForStore = array_values(array_unique($productIdsForStore));
                        $siteId = $this->resolveSiteIdByStoreId((int)$storeId);
                        $siteKey = $siteId ?? self::UNSCOPED_SITE_KEY;

                        $productNextActions = $this->productNextActionProvider->getNextActionsByProductIds(
                            $productIdsForStore,
                            (int)$storeId
                        );

                        foreach ($productIdsForStore as $productId) {
                            $nextAction = $productNextActions[$productId] ?? Actions::UPSERT;

                            if (!isset($resolvedActionsBySite[$siteKey][$productId]) || $nextAction === Actions::UPSERT) {
                                $resolvedActionsBySite[$siteKey][$productId] = $nextAction;
                            }
                        }
                    }
                } catch (\Throwable $chunkEx) {
                    $this->logger->error(
                       "[BunchSaveObserver] Chunk processing error: " . $chunkEx->getMessage(),
                        ['trace' => $chunkEx->getTraceAsString()]
                    );
                }
            }

            foreach ($resolvedActionsBySite as $siteKey => $resolvedActions) {
                $productIdsToUpsert = [];
                $productIdsToDelete = [];
                $siteIds = $siteKey === self::UNSCOPED_SITE_KEY ? [] : [$siteKey];

                foreach ($resolvedActions as $productId => $nextAction) {
                    if ($nextAction === Actions::DELETE) {
                        $productIdsToDelete[] = $productId;
                        continue;
                    }

                    $productIdsToUpsert[] = $productId;
                }

                if (!empty($productIdsToUpsert)) {
                    $productIdsToUpsert = array_values(array_unique($productIdsToUpsert));
                    $this->baseProductObserver->execute($productIdsToUpsert, Actions::UPSERT, true, $siteIds);
                    $this->logger->info(
                        '[BunchSaveObserver] Executed UPSERT for products',
                        ['productIds' => $productIdsToUpsert, 'site_id' => $siteKey]
                    );
                }

                if (!empty($productIdsToDelete)) {
                    $productIdsToDelete = array_values(array_unique($productIdsToDelete));
                    $productIdsToDelete = array_values(array_diff($productIdsToDelete, $productIdsToUpsert));
                }

                if (!empty($productIdsToDelete)) {
                    $this->baseProductObserver->execute($productIdsToDelete, Actions::DELETE, false, $siteIds);
                    $this->logger->info(
                        '[BunchSaveObserver] Executed DELETE for products',
                        ['productIds' => $productIdsToDelete, 'site_id' => $siteKey]
                    );
                }
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

    /**
     * @param int $storeId
     * @return string|null
     */
    private function resolveSiteIdByStoreId(int $storeId): ?string
    {
        $siteId = trim($this->configModel->getSiteIdByStoreId($storeId));

        return $siteId !== '' ? $siteId : null;
    }

}
