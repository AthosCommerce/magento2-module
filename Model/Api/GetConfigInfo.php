<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Api;

use AthosCommerce\Feed\Api\GetConfigInfoInterface;
use AthosCommerce\Feed\Api\Data\ConfigInfoResponseInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Config\ConfigMap;
use AthosCommerce\Feed\Model\Data\ConfigInfoResponseFactory;
use AthosCommerce\Feed\Model\Data\StoreConfigFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;

class GetConfigInfo implements GetConfigInfoInterface
{
    /** @var ResourceConnection */
    private $resource;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var AthosCommerceLogger */
    private $logger;

    /** @var ConfigInfoResponseFactory */
    private $responseFactory;

    /** @var StoreConfigFactory */
    private $storeConfigFactory;

    public function __construct(
        ResourceConnection        $resource,
        StoreManagerInterface     $storeManager,
        AthosCommerceLogger       $logger,
        ConfigInfoResponseFactory $responseFactory,
        StoreConfigFactory        $storeConfigFactory
    )
    {
        $this->resource = $resource;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->storeConfigFactory = $storeConfigFactory;
    }

    /**
     * @return ConfigInfoResponseInterface
     */
    public function get(): ConfigInfoResponseInterface
    {
        /** @var ConfigInfoResponseInterface $response */
        $response = $this->responseFactory->create();

        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('core_config_data');

            $pathToKeyMap = ConfigMap::getPathToKeyMap();
            $paths = array_keys($pathToKeyMap);

            $select = $connection->select()
                ->from($table, ['scope_id', 'path', 'value'])
                ->where('scope = ?', 'stores')
                ->where('path IN (?)', $paths)
                ->order('scope_id ASC');

            $rows = $connection->fetchAll($select);
            if (!$rows) {
                $response->setSuccess(true);
                $response->setMessage(__('No configuration found.')->render());
                $response->setStores([]);
                return $response;
            }

            $stores = [];
            $storeCodesCache = [];

            foreach ($rows as $row) {
                $storeId = (int)$row['scope_id'];
                $path = $row['path'];

                if (!isset($pathToKeyMap[$path])) {
                    continue;
                }

                $key = $pathToKeyMap[$path];
                $meta = ConfigMap::MAP[$key];
                $value = $row['value'];

                if (in_array($meta['type'], ['bool', 'int'], true)) {
                    $value = (int)$value;
                }

                if (!isset($storeCodesCache[$storeId])) {
                    try {
                        $storeCodesCache[$storeId] = $this->storeManager
                            ->getStore($storeId)
                            ->getCode();
                    } catch (\Exception $e) {
                        $storeCodesCache[$storeId] = null;
                    }
                }

                if (!isset($stores[$storeId])) {
                    $storeConfigModel = $this->storeConfigFactory->create();
                    $storeConfigModel->setStoreId((int)$storeId);
                    $storeConfigModel->setStoreCode($storeCodesCache[$storeId]);
                    $stores[$storeId] = $storeConfigModel;
                }

                $storeConfigModel->setData($key, $value);
                $stores[$storeId] = $storeConfigModel->toArray();
            }

            $response->setSuccess(true);
            $response->setMessage(
                __('Configuration fetched successfully. Secret values may appear encrypted.')->render()
            );
            $response->setStores(array_values($stores));

            $this->logger->info(
                'GetConfigInfo API: ',
                [
                    'method' => __METHOD__,
                    'storesInfo' => $stores,
                ]
            );

        } catch (\Throwable $e) {
            $this->logger->error(
                'GetConfigInfo API error: ' . $e->getMessage(),
                [
                    'method' => __METHOD__,
                    'exception' => $e->getTraceAsString(),
                ]
            );

            $response->setSuccess(false);
            $response->setMessage(__('Unable to fetch configuration.')->render());
            $response->setStores([]);
        }

        return $response;
    }
}
