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

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Model\Stock\Item;

class LegacyStockProvider implements StockProviderInterface
{
    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $legacyStockItemCriteriaFactory;
    /**
     * @var StockItemRepositoryInterface
     */
    private $legacyStockItemRepository;
    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * LegacyStockProvider constructor.
     * @param StockItemCriteriaInterfaceFactory $legacyStockItemCriteriaFactory
     * @param StockItemRepositoryInterface $legacyStockItemRepository
     * @param StockConfigurationInterface $stockConfiguration
     */
    public function __construct(
        StockItemCriteriaInterfaceFactory $legacyStockItemCriteriaFactory,
        StockItemRepositoryInterface $legacyStockItemRepository,
        StockConfigurationInterface $stockConfiguration
    ) {
        $this->legacyStockItemCriteriaFactory = $legacyStockItemCriteriaFactory;
        $this->legacyStockItemRepository = $legacyStockItemRepository;
        $this->stockConfiguration = $stockConfiguration;
    }

    /**
     * [
     *      product_id => [
     *          'qty' => float,
     *          'in_stock' => bool
     *      ],
     *      .........
     * ]
     *
     * @param array $productIds
     * @param int $storeId
     * @return array
     */
    public function getStock(array $productIds, int $storeId): array
    {
        if (!$productIds) {
            return [];
        }

        $searchCriteria = $this->legacyStockItemCriteriaFactory->create();
        $searchCriteria->setScopeFilter($this->stockConfiguration->getDefaultScopeId());
        $searchCriteria->setProductsFilter($productIds);
        $items = $this->legacyStockItemRepository->getList($searchCriteria)->getItems();
        $result = [];
        foreach ($items as $item) {
            if ($item) {
                /** @var Item $item */
                $item->setStoreId($storeId);

                $result[$item->getProductId()] = [
                    'qty' => $item->getQty(),
                    'in_stock' => $item->getIsInStock(),
                    'is_stock_managed' => $item->getManageStock()
                ];
            }
        }

        return $result;
    }
}
