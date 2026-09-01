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

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\Context\StoreContextManager;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
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
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @var array<string, Product|null>
     */
    private $resolvedParentCache = [];

    /**
     * @param StockResolverInterface $stockResolver
     * @param StoreContextManager $storeContextManager
     * @param ParentVariantResolver $parentVariantResolver
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        StockResolverInterface $stockResolver,
        StoreContextManager    $storeContextManager,
        ParentVariantResolver  $parentVariantResolver,
        AthosCommerceLogger    $logger
    )
    {
        $this->stockResolver = $stockResolver;
        $this->storeContextManager = $storeContextManager;
        $this->parentVariantResolver = $parentVariantResolver;
        $this->logger = $logger;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getData(
        array                      $products,
        FeedSpecificationInterface $feedSpecification
    ): array
    {
        $this->logger->info("[StockProvider] Started");

        $ignoreFields = $feedSpecification->getIgnoreFields();
        $stockKeys = ['__in_stock', 'in_stock', 'stock_qty', 'is_stock_managed'];

        if (empty(array_diff($stockKeys, $ignoreFields))) {
            return $products;
        }

        $productIds = [];
        $parentIds = [];
        $resolvedParentIdsByRowKey = [];

        foreach ($products as $row) {
            $productId = isset($row['entity_id']) ? (int)$row['entity_id'] : 0;
            if ($productId > 0) {
                $productIds[$productId] = $productId;
            }

            $productModel = $row['product_model'] ?? null;
            if (!$productModel instanceof Product) {
                continue;
            }

            $parentProduct = $this->resolveParentProductForRow($row, $productModel);
            if ($parentProduct instanceof Product) {
                $parentId = (int)$parentProduct->getId();
                $parentIds[$parentId] = $parentId;
                if ($productId > 0) {
                    $rowCacheKey = $this->getParentResolutionCacheKey($row, $productModel);
                    $resolvedParentIdsByRowKey[$rowCacheKey] = $parentId;
                }
            }
        }

        if (!$productIds) {
            return $products;
        }

        $stockProvider = $this->stockResolver->resolve($feedSpecification->getIsMsiEnabled());

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

            if ($productId <= 0) {
                continue;
            }

            $rowCacheKey = $this->getParentResolutionCacheKey($product, $productModel);
            if (!isset($resolvedParentIdsByRowKey[$rowCacheKey])) {
                continue;
            }

            $parentId = $resolvedParentIdsByRowKey[$rowCacheKey];
            if (!isset($parentStockData[$parentId])) {
                continue;
            }

            $pStock = $parentStockData[$parentId];
            $product['parent_in_stock'] = (int)($pStock['in_stock'] ?? 0);
            $product['parent_stock_qty'] = (float)($pStock['qty'] ?? 0);
            $product['parent_is_stock_managed'] = (int)($pStock['is_stock_managed'] ?? 0);
        }
        unset($product);
        $this->logger->info("[StockProvider] Completed");

        return $products;
    }

    public function reset(): void
    {
        $this->resolvedParentCache = [];
    }

    public function resetAfterFetchItems(): void
    {
        $this->reset();
    }

    /**
     * @param array $row
     * @param Product $productModel
     * @return Product|null
     */
    private function resolveParentProductForRow(array $row, Product $productModel): ?Product
    {
        $cacheKey = $this->getParentResolutionCacheKey($row, $productModel);
        if (!array_key_exists($cacheKey, $this->resolvedParentCache)) {
            $this->resolvedParentCache[$cacheKey] = $this->parentVariantResolver->resolveParentProductForRow($row, $productModel);
        }

        return $this->resolvedParentCache[$cacheKey];
    }

    /**
     * @param array $row
     * @param Product $productModel
     * @return string
     */
    private function getParentResolutionCacheKey(array $row, Product $productModel): string
    {
        return implode(':', [
            (string)$productModel->getId(),
            (string)($row[Constant::RESOLVED_PARENT_ID_KEY] ?? ''),
            (string)($row[Constant::RESOLVED_PARENT_SKU_KEY] ?? ''),
            (string)($row[Constant::RESOLVED_PARENT_TYPE_KEY] ?? ''),
            (string)($row[Constant::RESOLVED_PARENT_ROW_SOURCE_KEY] ?? ''),
        ]);
    }
}
