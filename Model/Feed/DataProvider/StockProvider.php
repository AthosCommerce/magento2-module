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
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\StockResolverInterface;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\Catalog\Model\Product;

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
     * @var ParentVariantResolver
     */
    private $parentVariantResolver;

    /**
     * @param StockResolverInterface $stockResolver
     * @param StoreContextManager $storeContextManager
     * @param ParentVariantResolver $parentVariantResolver
     */
    public function __construct(
        StockResolverInterface $stockResolver,
        StoreContextManager    $storeContextManager,
        ParentVariantResolver  $parentVariantResolver
    )
    {
        $this->stockResolver = $stockResolver;
        $this->storeContextManager = $storeContextManager;
        $this->parentVariantResolver = $parentVariantResolver;
    }

    public function getData(
        array                      $products,
        FeedSpecificationInterface $feedSpecification
    ): array
    {
        $ignoreFields = $feedSpecification->getIgnoreFields();
        $stockKeys = ['__in_stock', 'in_stock', 'stock_qty', 'is_stock_managed'];

        if (empty(array_diff($stockKeys, $ignoreFields))) {
            return $products;
        }

        $productIds = [];
        $parentIds = [];

        foreach ($products as $row) {
            $productId = isset($row['entity_id']) ? (int)$row['entity_id'] : 0;
            if ($productId > 0) {
                $productIds[$productId] = $productId;
            }

            $productModel = $row['product_model'] ?? null;
            if (!$productModel instanceof Product) {
                continue;
            }

            $parentProduct = $this->parentVariantResolver->resolveParentProductForRow($row, $productModel);
            if ($parentProduct instanceof Product) {
                $parentIds[(int)$parentProduct->getId()] = (int)$parentProduct->getId();
            }
        }

        if (!$productIds) {
            return $products;
        }

        $stockProvider = $this->stockResolver->resolve($feedSpecification->getIsMsiEnabled());
        $storeId = (int)$this->storeContextManager->getStoreFromContext()->getId();

        $childStockData = $stockProvider->getStock(array_values($productIds));
        $parentStockData = !empty($parentIds)
            ? $stockProvider->getStock(array_values($parentIds))
            : [];

        foreach ($products as &$product) {
            $productId = isset($product['entity_id']) ? (int)$product['entity_id'] : 0;
            if ($productId > 0 && isset($childStockData[$productId])) {
                $stockItem = $childStockData[$productId];

                if (!in_array('__in_stock', $ignoreFields, true) && isset($stockItem['in_stock'])) {
                    $product['__in_stock'] = (bool)$stockItem['in_stock'];
                }
                if (!in_array('in_stock', $ignoreFields, true) && isset($stockItem['in_stock'])) {
                    $product['in_stock'] = (int)$stockItem['in_stock'];
                }
                if (!in_array('stock_qty', $ignoreFields, true) && isset($stockItem['qty'])) {
                    $product['stock_qty'] = (float)$stockItem['qty'];
                }
                if (!in_array('is_stock_managed', $ignoreFields, true) && isset($stockItem['is_stock_managed'])) {
                    $product['is_stock_managed'] = (int)$stockItem['is_stock_managed'];
                }
            }

            $productModel = $product['product_model'] ?? null;
            if (!$productModel instanceof Product) {
                continue;
            }

            $parentProduct = $this->parentVariantResolver->resolveParentProductForRow($product, $productModel);
            if (!$parentProduct instanceof Product) {
                continue;
            }

            $parentId = (int)$parentProduct->getId();
            if (!isset($parentStockData[$parentId])) {
                continue;
            }

            $pStock = $parentStockData[$parentId];
            $product['parent_in_stock'] = (int)($pStock['in_stock'] ?? 0);
            $product['parent_stock_qty'] = (float)($pStock['qty'] ?? 0);
            $product['parent_is_stock_managed'] = (int)($pStock['is_stock_managed'] ?? 0);
        }
        unset($product);

        return $products;
    }

    public function reset(): void
    {
        // do nothing
    }

    public function resetAfterFetchItems(): void
    {
        // do nothing
    }
}
