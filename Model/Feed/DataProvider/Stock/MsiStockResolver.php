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

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Stock;

use Magento\Framework\Module\Manager;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class MsiStockResolver implements StockResolverInterface
{
    /**
     * @var Manager
     */
    private $moduleManager;
    /**
     * @var MsiStockProvider
     */
    private $msiStockProvider;
    /**
     * @var LegacyStockProvider
     */
    private $legacyStockProvider;
    /**
     * @var AthosCommerceLogger
     */
    protected $logger;
    /**
     * @var array
     */
    private $moduleList = [
        'Magento_InventoryReservationsApi',
        'Magento_InventorySalesApi',
        'Magento_InventoryCatalogApi',
    ];
    /**
     * @var bool|null
     */
    private $isMsiEnabledCache;

    /**
     * @param Manager $moduleManager
     * @param MsiStockProvider $msiStockProvider
     * @param LegacyStockProvider $legacyStockProvider
     * @param AthosCommerceLogger $logger
     * @param array $moduleList
     */
    public function __construct(
        Manager             $moduleManager,
        MsiStockProvider    $msiStockProvider,
        LegacyStockProvider $legacyStockProvider,
        AthosCommerceLogger $logger,
        array               $moduleList = []
    )
    {
        $this->moduleManager = $moduleManager;
        $this->msiStockProvider = $msiStockProvider;
        $this->legacyStockProvider = $legacyStockProvider;
        $this->logger = $logger;
        if (!empty($moduleList)) {
            $this->moduleList = array_values(array_unique($moduleList));
        }
    }

    /**
     * MSI stock resolver
     *
     * @param bool $isMsiEnabled
     * @return StockProviderInterface
     */
    public function resolve(bool $isMsiEnabled): StockProviderInterface
    {
        if (!$isMsiEnabled) {
            $this->logger->info(
                'MSI Check',
                [
                    'method' => __METHOD__,
                    'isInventoryModulesEnabled' => false,
                    'message' => 'MSI payload disabled. Using LegacyStockProvider for stock resolution.'
                ]
            );
            return $this->legacyStockProvider;
        }

        $isInventoryModulesEnabled = $this->isInventoryModulesEnabled();
        if ($isInventoryModulesEnabled) {
            $this->logger->info(
                'MSI Check',
                [
                    'method' => __METHOD__,
                    'isInventoryModulesEnabled' => $isInventoryModulesEnabled,
                    'message' => 'MSI modules are installed and enabled. Using MsiStockProvider for stock resolution.'
                ]
            );
            return $this->msiStockProvider;
        }
        $this->logger->info(
            'MSI Check',
            [
                'method' => __METHOD__,
                'isInventoryModulesEnabled' => $isInventoryModulesEnabled,
                'message' => 'MSI modules are not installed. Using LegacyStockProvider for stock resolution.'
            ]
        );
        return $this->legacyStockProvider;
    }

    /**
     * Determine if msi enable or not
     *
     * @return bool
     */
    private function isInventoryModulesEnabled(): bool
    {
        if ($this->isMsiEnabledCache !== null) {
            return $this->isMsiEnabledCache;
        }

        foreach ($this->moduleList as $moduleName) {
            if (!$this->moduleManager->isEnabled($moduleName)) {
                $this->isMsiEnabledCache = false;
                return false;
            }
        }

        $this->isMsiEnabledCache = true;
        return true;
    }
}
