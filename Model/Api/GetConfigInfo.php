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
use AthosCommerce\Feed\Model\Config\StoreConfigApiMapper;
use AthosCommerce\Feed\Model\Data\ConfigInfoResponseFactory;
use AthosCommerce\Feed\Model\Data\StoreConfigFactory;
use \AthosCommerce\Feed\Model\ConfigRepository;
use Magento\Store\Model\StoreManagerInterface;

class GetConfigInfo implements GetConfigInfoInterface
{
    /** @var ConfigRepository */
    private $configRepository;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var AthosCommerceLogger */
    private $logger;

    /** @var ConfigInfoResponseFactory */
    private $responseFactory;

    /** @var StoreConfigFactory */
    private $storeConfigFactory;

    /** @var StoreConfigApiMapper */
    private $storeConfigApiMapper;

    /**
     * @param ConfigRepository $configRepository
     * @param StoreManagerInterface $storeManager
     * @param AthosCommerceLogger $logger
     * @param ConfigInfoResponseFactory $responseFactory
     * @param StoreConfigFactory $storeConfigFactory
     * @param StoreConfigApiMapper $storeConfigApiMapper
     */
    public function __construct(
        ConfigRepository          $configRepository,
        StoreManagerInterface     $storeManager,
        AthosCommerceLogger       $logger,
        ConfigInfoResponseFactory $responseFactory,
        StoreConfigFactory        $storeConfigFactory,
        StoreConfigApiMapper      $storeConfigApiMapper
    )
    {
        $this->configRepository = $configRepository;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->storeConfigFactory = $storeConfigFactory;
        $this->storeConfigApiMapper = $storeConfigApiMapper;
    }

    /**
     * @return ConfigInfoResponseInterface
     */
    public function get(): ConfigInfoResponseInterface
    {
        /** @var ConfigInfoResponseInterface $response */
        $response = $this->responseFactory->create();

        try {
            $rows = $this->configRepository->fetchStoreConfigRows();
            if (!$rows) {
                $response->setSuccess(true);
                $response->setMessage(__('No configuration found.')->render());
                $response->setStores([]);
                return $response;
            }

            $stores = [];
            $storeCodesCache = [];
            $pathToKeyMap = ConfigMap::getPathToKeyMap();

            foreach ($rows as $row) {
                $storeId = (int)$row['scope_id'];
                $path = $row['path'];

                if (!isset($pathToKeyMap[$path])) {
                    continue;
                }
                $outputKey = $pathToKeyMap[$path];
                $meta = ConfigMap::MAP[$outputKey] ?? null;
                if ($meta === null) {
                    continue;
                }
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
                    /** @var StoreConfigFactory $storeConfigModel */
                    $storeConfigModel = $this->storeConfigFactory->create();
                    $storeConfigModel->setStoreId((int)$storeId);
                    $storeConfigModel->setStoreCode($storeCodesCache[$storeId]);
                    $stores[$storeId] = $storeConfigModel;
                }

                $setter = 'set' . ucfirst($outputKey);
                if (method_exists($stores[$storeId], $setter)) {
                    $stores[$storeId]->{$setter}($value);
                } else {
                    $this->logger->warning(
                        'Setter method not found in StoreConfig model',
                        [
                            'method' => __METHOD__,
                            'storeId' => $storeId,
                            'setter' => $setter,
                            'key' => $outputKey,
                            'path' => $path,
                            'value' => $value
                        ]
                    );
                }
                $stores[$storeId] = $storeConfigModel;
            }
            $apiStores = [];
            foreach ($stores as $storeConfig) {
                $apiStores[] = $this->storeConfigApiMapper->map($storeConfig);
            }

            $response
                ->setSuccess(true)
                ->setMessage(__('Configuration fetched successfully.')->render())
                ->setStores($apiStores);

            $this->logger->info(
                'GetConfigInfo API',
                [
                    'method' => __METHOD__,
                    'stores' => $apiStores,
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
