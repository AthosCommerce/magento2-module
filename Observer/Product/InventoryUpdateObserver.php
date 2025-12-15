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
use AthosCommerce\Feed\Observer\BaseProductObserver;
use Magento\Catalog\Model\ProductRepository;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class InventoryUpdateObserver implements ObserverInterface
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
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        LoggerInterface      $logger,
        ProductRepository    $productRepository,
        ScopeConfigInterface $scopeConfig,
        BaseProductObserver  $baseProductObserver
    )
    {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->scopeConfig = $scopeConfig;
        $this->baseProductObserver = $baseProductObserver;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Item $stockItem */
        $stockItem = $observer->getEvent()->getItem();
        $productId = $stockItem->getProductId();

        try {

            $qtyChanged = $stockItem->dataHasChangedFor('qty');
            $inStockChanged = $stockItem->dataHasChangedFor('is_in_stock');

            if (!$qtyChanged && !$inStockChanged) {
                return;
            }

            $product = $this->productRepository->getById(
                $productId,
                false,
                null,
                true
            );
            if(!$product || !$product->getId()) {
                return;
            }
            $storeIds = method_exists($product, 'getStoreIds') ? $product->getStoreIds() : [];

            foreach ($storeIds as $storeId) {
                try {
                    $liveIndexing = (bool)$this->scopeConfig->getValue(
                        Constants::XML_PATH_LIVE_INDEXING_ENABLED,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        $storeId
                    );

                    if (!$liveIndexing) {
                        continue;
                    }

                    $nextAction = ($product->getStatus() != 1 || $product->getVisibility() == 1)
                        ? Actions::DELETE
                        : Actions::UPSERT;

                    $this->baseProductObserver->execute([$productId], $nextAction);

                    $this->logger->debug('[InventoryUpdateObserver] Stock Update Store Check', [
                        'product_id'    => $productId,
                        'store_id'      => $storeId,
                        'live_indexing' => $liveIndexing,
                        'action'        => $nextAction
                    ]);

                } catch (\Throwable $e) {
                    $this->logger->error('[InventoryUpdateObserver] Error processing stock for store ' . $storeId, [
                        'product_id' => $productId,
                        'message' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

        } catch (\Throwable $e) {
            $this->logger->error('[InventoryUpdateObserver] Exception thrown', [
                'product_id' => $productId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
