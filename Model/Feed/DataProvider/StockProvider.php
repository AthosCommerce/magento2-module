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

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\Context\StoreContextManager;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\RelationsProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\StockResolverInterface;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;

class StockProvider implements DataProviderInterface
{
    /**
     * @var StockResolverInterface
     */
    private $stockResolver;
    /**
     * @var StoreContextManager
     */
    private $storeContextManager;
    /**
     * @var RelationsProvider
     */
    private $relationsProvider;

    /**
     * @param StockResolverInterface $stockResolver
     * @param StoreManagerInterface $storeManager
     * @param RelationsProvider $relationsProvider
     */
    public function __construct(
        StockResolverInterface $stockResolver,
        StoreContextManager $storeContextManager,
        RelationsProvider $relationsProvider
    ) {
        $this->stockResolver = $stockResolver;
        $this->storeContextManager = $storeContextManager;
        $this->relationsProvider = $relationsProvider;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(
        array $products,
        FeedSpecificationInterface $feedSpecification
    ): array {
        $ignoreFields = $feedSpecification->getIgnoreFields();
        $stockKeys = ['__in_stock', 'in_stock', 'stock_qty', 'is_stock_managed'];

        if (empty(array_diff($stockKeys, $ignoreFields))) {
            return $products;
        }

        $childIds = [];
        foreach ($products as $productRow) {
            if (isset($productRow['entity_id'])) {
                $childIds[] = (int)$productRow['entity_id'];
            }
        }

        if (!$childIds) {
            return $products;
        }

        $stockProvider = $this->stockResolver->resolve($feedSpecification->getIsMsiEnabled());
        $storeId = (int)$this->storeContextManager->getStoreFromContext()->getId();

        $childStockData = $stockProvider->getStock($childIds);

        $childToParentMap = $this->relationsProvider->getConfigurableRelationIds($childIds);

        $parentIds = [];
        foreach ($childToParentMap as $childId => $parents) {
            foreach ($parents as $parentId) {
                $parentIds[$parentId] = $parentId;
            }
        }

        $parentStockData = [];
        if ($parentIds) {
            $parentStockData = $stockProvider->getStock(array_values($parentIds), $storeId);
        }

        foreach ($products as &$product) {
            $childId = $product['entity_id'] ?? null;
            if (!$childId) {
                continue;
            }

            if (isset($childStockData[$childId])) {
                $stockItem = $childStockData[$childId];
                if (!in_array('__in_stock', $ignoreFields) && isset($stockItem['in_stock'])) {
                    $product['__in_stock'] = (boolean)$stockItem['in_stock'];
                }
                if (!in_array('in_stock', $ignoreFields) && isset($stockItem['in_stock'])) {
                    $product['in_stock'] = (int)$stockItem['in_stock'];
                }

                if (!in_array('stock_qty', $ignoreFields) && isset($stockItem['qty'])) {
                    $product['stock_qty'] = (float)$stockItem['qty'];
                }

                if (!in_array('is_stock_managed', $ignoreFields) && isset($stockItem['is_stock_managed'])) {
                    $product['is_stock_managed'] = (int)$stockItem['is_stock_managed'];
                }
            }

            if (!isset($childToParentMap[$childId])) {
                continue;
            }

            foreach ($childToParentMap[$childId] as $parentId) {
                if (!isset($parentStockData[$parentId])) {
                    continue;
                }

                $pStock = $parentStockData[$parentId];

                $product["parent_in_stock"] = (int)($pStock['in_stock'] ?? 0);
                $product["parent_stock_qty"] = (float)($pStock['qty'] ?? 0);
                $product["parent_is_stock_managed"] = (int)($pStock['is_stock_managed'] ?? 0);
            }
        }

        return $products;
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        //do nothing
    }

    /**
     * @return void
     */
    public function resetAfterFetchItems(): void
    {
        //do nothing
    }
}
