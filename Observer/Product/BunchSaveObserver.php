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

use AthosCommerce\Feed\Model\Source\Actions;
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

    private ResourceConnection $resource;

    /**
     * @param BaseProductObserver $baseProductObserver
     * @param LoggerInterface $logger
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        BaseProductObserver $baseProductObserver,
        LoggerInterface     $logger,
        ResourceConnection  $resourceConnection
    )
    {
        $this->baseProductObserver = $baseProductObserver;
        $this->logger = $logger;
        $this->resource = $resourceConnection;
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

      //  $nextAction = Actions::UPSERT;

        $skus = array_column($bunch, 'sku');

        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('catalog_product_entity');
        $select = $connection->select()
            ->from($table, ['sku', 'entity_id'])
            ->where('sku IN (?)', $skus);

        $skuToData = $connection->fetchAll($select);
        $entityIds = [];
        foreach ($skuToData as $data) {
            $entityIds[] = $data['entity_id'];
//            $this->logger->debug(
//                "Product SKU: {$data['sku']}, ID: {$data['entity_id']}, Next Action: $nextAction"
//            );
        }

        $this->baseProductObserver->execute($entityIds, Actions::UPSERT);

        $this->logger->debug(
            'BunchSaveObserver executed: saved product IDs',
            [
                'skus' => $skus,
                'ids' => $entityIds
            ]
        );
    }
}
