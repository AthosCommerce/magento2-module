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

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\StockResolverInterface;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;

class StockProvider implements DataProviderInterface
{
    /**
     * @var StockResolverInterface
     */
    private $stockResolver;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * StockProvider constructor.
     * @param StockResolverInterface $stockResolver
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StockResolverInterface $stockResolver,
        StoreManagerInterface $storeManager
    ) {
        $this->stockResolver = $stockResolver;
        $this->storeManager = $storeManager;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $ignoreFields = $feedSpecification->getIgnoreFields();
        $productIds = [];
        foreach ($products as $product) {
            if (isset($product['entity_id'])) {
                $productIds[] = (int) $product['entity_id'];
            }
        }

        if (empty($productIds)) {
            return $products;
        }

        $stockProvider = $this->stockResolver->resolve($feedSpecification->getMsiStatus());
        $storeId = (int) $this->storeManager->getStore($feedSpecification->getStoreCode())->getId();
        $stockData = $stockProvider->getStock($productIds, $storeId);
        foreach ($products as &$product) {
            $productId = $product['entity_id'] ?? null;
            if ($productId && isset($stockData[$productId])) {
                $stockItem = $stockData[$productId];
                if (!in_array('in_stock', $ignoreFields) && isset($stockItem['in_stock'])) {
                    $product['in_stock'] = (int) $stockItem['in_stock'];
                }

                if (!in_array('stock_qty', $ignoreFields) && isset($stockItem['qty'])) {
                    $product['stock_qty'] = (float) $stockItem['qty'];
                }

                if (!in_array('is_stock_managed', $ignoreFields) && isset($stockItem['is_stock_managed'])) {
                    $product['is_stock_managed'] = (int) $stockItem['is_stock_managed'];
                }
            }
        }

        return $products;
    }

    /**
     *
     */
    public function reset(): void
    {
        // do nothing
    }

    /**
     *
     */
    public function resetAfterFetchItems(): void
    {
        // do nothing
    }
}
