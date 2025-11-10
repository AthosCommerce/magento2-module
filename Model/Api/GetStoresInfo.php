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

namespace AthosCommerce\Feed\Model\Api;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\ConfigInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use AthosCommerce\Feed\Api\GetStoresInfoInterface;
use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;

class GetStoresInfo implements GetStoresInfoInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ConfigInterface
     */
    private $viewConfig;

    /**
     * @var Emulation
     */
    private $emulation;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ConfigInterface $viewConfig
     * @param Emulation $emulation
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ConfigInterface $viewConfig,
        Emulation $emulation,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->storeManager = $storeManager;
        $this->viewConfig = $viewConfig;
        $this->emulation = $emulation;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return string
     */
    public function getAsHtml(): string
    {
        $result = '<h1>Stores</h1><ul>';
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $storeId = (int)$store->getId();
            $name = $store->getName();
            $code = $store->getCode();
            $result .= "<li>$storeId . $name - $code</li>";
            $result .= '<h2>Store Images</h2><ul>';
            $viewConfig = $this->getViewConfigWithStoreEmulation($storeId);
            foreach ($viewConfig as $id => $image) {
                $result .= "<li>$id<ul>";
                foreach ($image as $attr => $val) {
                    $result .= "<li>$attr = $val</li>";
                }
                $result .= '</ul></li>';
            }
            $result .= '</ul>';
        }
        $result .= '</ul>';

        return $result;
    }

    /**
     * @return array
     */
    public function getAsJson(): array
    {
        $stores = $this->storeManager->getStores();

        $preparedResult = [];
        foreach ($stores as $store) {
            $storeId = (int)$store->getId();
            $preparedResult[] = [
                'store_id' => $storeId,
                'name' => $store->getName(),
                'code' => $store->getCode(),
                'theme_id' => $this->scopeConfig->getValue(
                    DesignInterface::XML_PATH_THEME_ID,
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                ),
                'images' => $this->getViewConfigWithStoreEmulation($storeId),
            ];
        }

        return $preparedResult;
    }

    /**
     * @param int $storeId
     *
     * @return array
     */
    private function getViewConfigWithStoreEmulation(int $storeId): array
    {
        $this->emulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
        $config = $this->viewConfig->getViewConfig()->read();
        $this->emulation->stopEnvironmentEmulation();

        return $config['media']['Magento_Catalog']['images'] ?? [];
    }
}
