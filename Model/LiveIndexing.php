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

use AthosCommerce\Feed\Api\LiveIndexingInterface;
use AthosCommerce\Feed\Helper\Constants;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use AthosCommerce\Feed\Model\LiveIndexing\Processor;
use AthosCommerce\Feed\Model\LiveIndexing\StoreLock;
use AthosCommerce\Feed\Model\Config as ConfigModel;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class LiveIndexing implements LiveIndexingInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ConfigModel
     */
    private $config;
    /**
     * @var Processor
     */
    private $processor;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;
    /**
     * @var StoreLock
     */
    private $storeLock;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ConfigModel $config
     * @param Processor $processor
     * @param AthosCommerceLogger $logger
     * @param StoreLock $storeLock
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ConfigModel           $config,
        Processor             $processor,
        AthosCommerceLogger       $logger,
        StoreLock                 $storeLock
    )
    {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->processor = $processor;
        $this->logger = $logger;
        $this->storeLock = $storeLock;
    }

    /**
     * @param array|null $storeCodes
     *
     * @return array
     */
    public function execute(?array $storeCodes = null): array
    {
        $storesToProcess = [];
        $processCount = [];

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
            $storeCode = $store->getCode();

            $endPoint = $this->config->getEndpointByStoreId($storeId);
            $liveIndexingStatus = $this->config->isLiveIndexingEnabled($storeId);
            if(!$endPoint || !$liveIndexingStatus) {
                continue;
            }
            $siteId = $this->config->getSiteIdByStoreId($storeId);
            if ($siteId === false) {
                $this->logger->info(
                    "[LiveIndexing] Configuration incomplete for store: " . $storeCode,
                    [
                        'endpoint' => $endPoint ?? '',
                        'status' => $liveIndexingStatus ?? '',
                        'siteId' => $siteId ?? '',
                    ]
                );
                continue;
            }
            // One sync run per store at a time, whoever started it (cron worker or CLI).
            if (!$this->storeLock->acquire(StoreLock::TYPE_SYNC, $storeCode)) {
                $this->logger->info(
                    sprintf("[LiveIndexing] Skipped for store:%s | SiteID:%s: already running", $storeCode, $siteId)
                );
                continue;
            }
            try {
                $this->logger->info(
                    sprintf(
                        "[LiveIndexing] Processing start for store:%s | SiteID:%s",
                        $storeCode,
                        $siteId
                    )
                );
                $processCount[$storeCode] = $this->processor->execute(
                    $store,
                    $siteId
                );
                $this->logger->info(
                    sprintf(
                        "[LiveIndexing] Processing completed for store:%s | SiteID:%s",
                        $storeCode,
                        $siteId
                    )
                );
            } catch (\Throwable $exception) {
                // Logged per store (workers run detached); the next stores are still processed.
                $this->logger->error(
                    sprintf(
                        "[LiveIndexing] Processing failed for store:%s | SiteID:%s: %s",
                        $storeCode,
                        $siteId,
                        $exception->getMessage()
                    ),
                    ['store' => $storeCode, 'site_id' => $siteId, 'trace' => $exception->getTraceAsString()]
                );
            } finally {
                $this->storeLock->release(StoreLock::TYPE_SYNC, $storeCode);
            }
        }

        return $processCount;
    }

    /**
     * @param int $storeId
     *
     * @return bool
     */
    private function shouldLiveIndexingProcess(int $storeId): bool
    {
        return $this->config->getEndpointByStoreId($storeId)
            && $this->config->isLiveIndexingEnabled($storeId)
            && $this->config->getSiteIdByStoreId($storeId);
    }
}
