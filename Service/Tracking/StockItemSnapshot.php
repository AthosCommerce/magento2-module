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

namespace AthosCommerce\Feed\Service\Tracking;

use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Framework\App\ResourceConnection;

/**
 * Stored qty / is_in_stock of a stock item, read just before it is saved.
 *
 * A product save re-saves its stock item from the product's stock data without orig data,
 * so the item alone cannot tell whether stock changed. Comparing with the stored row can.
 */
class StockItemSnapshot
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var array<string, array{qty: float, is_in_stock: int}>
     */
    private $snapshots = [];

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param Item $stockItem
     * @return void
     */
    public function remember(Item $stockItem): void
    {
        $key = $this->getKey($stockItem);
        if ($key === null) {
            return;
        }
        $connection = $this->resourceConnection->getConnection();
        $row = $connection->fetchRow(
            $connection->select()
                ->from($this->resourceConnection->getTableName('cataloginventory_stock_item'), ['qty', 'is_in_stock'])
                ->where('product_id = ?', (int)$stockItem->getProductId())
                ->where('stock_id = ?', (int)($stockItem->getStockId() ?: 1))
        );
        if ($row) {
            $this->snapshots[$key] = ['qty' => (float)$row['qty'], 'is_in_stock' => (int)$row['is_in_stock']];
        } else {
            unset($this->snapshots[$key]);
        }
    }

    /**
     * Whether the field differs from the stored value; null when nothing was stored (new item).
     *
     * @param Item $stockItem
     * @param string $field qty|is_in_stock
     * @return bool|null
     */
    public function hasChanged(Item $stockItem, string $field): ?bool
    {
        $key = $this->getKey($stockItem);
        if ($key === null || !isset($this->snapshots[$key][$field])) {
            return null;
        }
        $stored = $this->snapshots[$key][$field];

        return $field === 'qty'
            ? abs((float)$stockItem->getData($field) - $stored) > 0.0001
            : (int)$stockItem->getData($field) !== $stored;
    }

    /**
     * @param Item $stockItem
     * @return void
     */
    public function forget(Item $stockItem): void
    {
        $key = $this->getKey($stockItem);
        if ($key !== null) {
            unset($this->snapshots[$key]);
        }
    }

    /**
     * @param Item $stockItem
     * @return string|null
     */
    private function getKey(Item $stockItem): ?string
    {
        $productId = (int)$stockItem->getProductId();

        return $productId > 0 ? $productId . ':' . (int)($stockItem->getStockId() ?: 1) : null;
    }
}
