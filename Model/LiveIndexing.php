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
     * @param StoreManagerInterface $storeManager
     * @param ConfigModel $config
     * @param Processor $processor
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ConfigModel           $config,
        Processor             $processor,
        AthosCommerceLogger       $logger
    )
    {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->processor = $processor;
        $this->logger = $logger;
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
            $isValid = $this->shouldLiveIndexingProcess($storeId);
            $siteId = $this->config->getSiteIdByStoreId($storeId);
            if ($isValid === false) {
                $this->logger->info(
                    "[LiveIndexing] Configuration incomplete for store: " . $storeCode,
                    [
                        'endpoint' => $this->config->getEndpointByStoreId($storeId),
                        'status' => $this->config->isLiveIndexingEnabled($storeId),
                        'siteId' => $siteId,
                    ]
                );
                continue;
            }
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
