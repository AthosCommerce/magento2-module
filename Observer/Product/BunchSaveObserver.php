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
use AthosCommerce\Feed\Model\Source\Actions;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use AthosCommerce\Feed\Observer\BaseProductObserver;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResourceConnection;

class BunchSaveObserver implements ObserverInterface
{
    /**
     * @var BaseProductObserver
     */
    private $baseProductObserver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param BaseProductObserver $baseProductObserver
     * @param LoggerInterface $logger
     * @param ResourceConnection $resourceConnection
     * @param ProductRepositoryInterface $productRepository
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        BaseProductObserver        $baseProductObserver,
        LoggerInterface            $logger,
        ResourceConnection         $resourceConnection,
        ProductRepositoryInterface $productRepository,
        ScopeConfigInterface       $scopeConfig
    )
    {
        $this->baseProductObserver = $baseProductObserver;
        $this->logger = $logger;
        $this->resource = $resourceConnection;
        $this->productRepository = $productRepository;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $bunch = (array)$event->getBunch();

        if (empty($bunch)) {
            $this->logger->debug("Bunch is empty.");
            return;
        }

        $skus = array_column($bunch, 'sku');

        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('catalog_product_entity');
        $select = $connection->select()
            ->from($table, ['sku', 'entity_id'])
            ->where('sku IN (?)', $skus);

        $skuToData = $connection->fetchAll($select);

        foreach ($skuToData as $data) {
            $entityId = $data['entity_id'];

            try {
                $product = $this->productRepository->getById($entityId);

                /** @var array $storeIds */
                $storeIds = $product->getStoreIds();

                foreach ($storeIds as $storeId) {
                    // Check the live indexing flag for this store
                    $liveIndexing = (bool)$this->scopeConfig->getValue(
                        Constants::XML_PATH_LIVE_INDEXING_ENABLED,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        $storeId
                    );

                    if (!$liveIndexing) {
                        continue; // skip this store
                    }

                    // Execute your observer action
                    $this->baseProductObserver->execute([$entityId], Actions::UPSERT);

                    $this->logger->debug(
                        'BunchSaveObserver executed: saved product ID',
                        [
                            'sku' => $data['sku'],
                            'id' => $entityId,
                            'storeId' => $storeId
                        ]
                    );
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->logger->error("Product not found: SKU {$data['sku']} ID {$entityId}");
            } catch (\Exception $e) {
                $this->logger->error("Error processing product ID {$entityId}: " . $e->getMessage());
            }
        }
    }
}
