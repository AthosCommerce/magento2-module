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
use Psr\Log\LoggerInterface;

class LiveIndexing implements LiveIndexingInterface
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
     * @var Processor
     */
    private $processor;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Processor $processor
     * @param LoggerInterface $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        Processor $processor,
        LoggerInterface $logger
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
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
            $storeId = $store->getId();
            $storeCode = $store->getCode();
            $isEnabled = (bool)$this->scopeConfig->getValue(
                Constants::XML_PATH_LIVE_INDEXING_ENABLED,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
            if (!$isEnabled) {
                $this->logger->info(
                    "Found indexing disabled   for store: " . $storeCode
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
                    "Found  site id not found for store: " . $storeCode
                );
                continue;
            }

            $endPoint = $this->scopeConfig->getValue(
                Constants::XML_PATH_CONFIG_ENDPOINT,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );

            if (!$endPoint) {
                $this->logger->error(
                    "Missing API Endpoint config for store: " . $storeCode
                );
                continue;
            }
            $shopDomain = $this->scopeConfig->getValue(
                Constants::XML_PATH_CONFIG_SHOP_DOMAIN,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
            if (!$shopDomain) {
                $this->logger->error(
                    "Missing Shop Domain config for store: " . $storeCode
                );
                continue;
            }
            $this->logger->info(
                "Processing start for store: " . $storeCode
            );
            $processCount[$storeCode] = $this->processor->execute(
                $store,
                $siteId
            );
        }

        return $processCount;
    }
}
