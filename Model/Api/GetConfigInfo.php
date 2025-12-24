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
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Config\ConfigMap;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

class GetConfigInfo implements GetConfigInfoInterface
{
    /** @var ResourceConnection */
    private $resource;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        ResourceConnection    $resource,
        StoreManagerInterface $storeManager,
        AthosCommerceLogger   $logger
    )
    {
        $this->resource = $resource;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public function get(): array
    {
        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('core_config_data');

            $pathToKeyMap = ConfigMap::getPathToKeyMap();
            $paths = array_keys($pathToKeyMap);

            $select = $connection->select()
                ->from($table, ['scope', 'scope_id', 'path', 'value'])
                ->where('scope = ?', 'stores')
                ->where('path IN (?)', $paths)
                ->order(['scope_id ASC']);

            $rows = $connection->fetchAll($select);

            if (empty($rows)) {
                return [
                    'data' => [
                        'success' => true,
                        'results' => ['stores' => []],
                    ],
                ];
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
                    $stores[$storeId] = [
                        'storeId' => $storeId,
                        'storeCode' => $storeCodesCache[$storeId],
                    ];
                }

                $stores[$storeId][$key] = $value;
            }

            return [
                'data' => [
                    'success' => true,
                    'message' => __('Configuration fetched successfully. Secret values may show in encrypted form'),
                    'results' => [
                        'stores' => array_values($stores),
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            $this->logger->error(
                'GetConfigInfo API error: ' . $e->getMessage(),
                [
                    'method' => __METHOD__,
                    'exception' => $e->getTraceAsString(),
                ]
            );
            return [
                'data' => [
                    'success' => false,
                    'message' => __('Unable to fetch configuration.')
                ],
            ];
        }
    }
}
