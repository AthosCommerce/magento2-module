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

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class MsiStockResolver implements StockResolverInterface
{
    /**
     * @var Manager
     */
    private $moduleManager;
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
     * MsiStockResolver constructor.
     *
     * @param Manager $moduleManager
     * @param array $moduleList
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        Manager $moduleManager,
        AthosCommerceLogger $logger,
        array $moduleList = [],

    ) {
        $this->moduleManager = $moduleManager;
        $this->moduleList = array_merge($this->moduleList, $moduleList);
        $this->logger = $logger;
    }

    /**
     * @return StockProviderInterface
     * @throws NoSuchEntityException
     */
    public function resolve(bool $isMsiEnabled): StockProviderInterface
    {
        if (!empty($isMsiEnabled) && $this->isMsiEnabled()) {
            $this->logger->info(
                'MSI Check',
                [
                    'method' => __METHOD__,
                    'isMsiEnabledViaPayload' => $isMsiEnabled,
                    'isMsiModuleEnabled' => $this->isMsiEnabled(),
                    'message' => 'MSI is enabled via payload and MSI module is enabled. Using MsiStockProvider for stock resolution.',
                ]
            );

            return ObjectManager::getInstance()
                ->get('\AthosCommerce\Feed\Model\Feed\DataProvider\Stock\MsiStockProvider');
        } else {
            $this->logger->info(
                'MSI Check',
                [
                    'method' => __METHOD__,
                    'isMsiEnabledViaPayload' => $isMsiEnabled,
                    'isMsiModuleEnabled' => $this->isMsiEnabled(),
                    'message' => 'MSI is disabled via payload or MSI modules are not installed. Using LegacyStockProvider for stock resolution.',
                ]
            );

            return ObjectManager::getInstance()
                ->get('\AthosCommerce\Feed\Model\Feed\DataProvider\Stock\LegacyStockProvider');
        }
    }

    /**
     * @return bool
     */
    private function isMsiEnabled(): bool
    {
        $moduleExists = true;
        foreach ($this->moduleList as $moduleName) {
            if (!$this->moduleManager->isEnabled($moduleName)) {
                $moduleExists = false;
                break;
            }
        }

        if (!$moduleExists) {
            return false;
        }

        return true;
    }
}
