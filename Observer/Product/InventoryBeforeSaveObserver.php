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

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Service\Tracking\StockItemSnapshot;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Stores the current qty / is_in_stock so InventoryUpdateObserver can tell a real stock change
 * from a product save that only re-saves the stock item.
 */
class InventoryBeforeSaveObserver implements ObserverInterface
{
    /**
     * @var StockItemSnapshot
     */
    private $stockItemSnapshot;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param StockItemSnapshot $stockItemSnapshot
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        StockItemSnapshot $stockItemSnapshot,
        AthosCommerceLogger $logger
    ) {
        $this->stockItemSnapshot = $stockItemSnapshot;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            $stockItem = $observer->getEvent()->getItem();
            if ($stockItem instanceof Item) {
                $this->stockItemSnapshot->remember($stockItem);
            }
        } catch (\Throwable $e) {
            $this->logger->error('[InventoryBeforeSaveObserver] ' . $e->getMessage());
        }
    }
}
