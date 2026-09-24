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

use AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface;
use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Model\Config as ConfigModel;
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Observer\BaseProductObserver;
use Magento\Catalog\Model\ProductRepository;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Service\Provider\ProductNextActionProvider;
use AthosCommerce\Feed\Service\Tracking\IdProviderInterface;
use AthosCommerce\Feed\Service\Tracking\StockItemSnapshot;
use Magento\Framework\App\ObjectManager;
use Magento\Catalog\Model\Product\Visibility;

class InventoryUpdateObserver implements ObserverInterface
{

    /**
     * @var BaseProductObserver
     */
    private $baseProductObserver;
    /**
     * @var AthosCommerceLogger
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
    /**
     * @var ProductNextActionProvider
     */
    private $productNextActionProvider;

    /**
     * @var IdProviderInterface
     */
    private $idProvider;

    /**
     * @var ConfigModel
     */
    private $configModel;

    /**
     * @var IndexingEntityRepositoryInterface
     */
    private $indexingEntityRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var StockItemSnapshot
     */
    private $stockItemSnapshot;

    public function __construct(
        AthosCommerceLogger       $logger,
        ProductRepository         $productRepository,
        ScopeConfigInterface      $scopeConfig,
        BaseProductObserver       $baseProductObserver,
        ProductNextActionProvider $productNextActionProvider,
        IdProviderInterface       $idProvider,
        ConfigModel               $configModel,
        IndexingEntityRepositoryInterface $indexingEntityRepository,
        SearchCriteriaBuilderFactory      $searchCriteriaBuilderFactory,
        ?StockItemSnapshot                $stockItemSnapshot = null
    )
    {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->scopeConfig = $scopeConfig;
        $this->baseProductObserver = $baseProductObserver;
        $this->productNextActionProvider = $productNextActionProvider;
        $this->idProvider = $idProvider;
        $this->configModel = $configModel;
        $this->indexingEntityRepository = $indexingEntityRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->stockItemSnapshot = $stockItemSnapshot
            ?? ObjectManager::getInstance()->get(StockItemSnapshot::class);
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

            $qtyChanged = $this->hasStockFieldChanged($stockItem, 'qty');
            $inStockChanged = $this->hasStockFieldChanged($stockItem, 'is_in_stock');
            $this->stockItemSnapshot->forget($stockItem);

            if (!$qtyChanged && !$inStockChanged) {
                return;
            }

            $product = $this->productRepository->getById(
                $productId,
                false,
                null,
                true
            );
            if (!$product || !$product->getId()) {
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

                    // Status/visibility are store-view scoped: resolve them for this store's site.
                    $storeProduct = $this->productNextActionProvider->getStoreScopedProduct(
                        (int)$productId,
                        (int)$storeId
                    ) ?? $product;
                    $nextAction = $this->productNextActionProvider->getNextActionByProduct(
                        $storeProduct,
                        (int)$storeId
                    );
                    $forceIndexable = $nextAction === Actions::UPSERT
                        && (int)$storeProduct->getVisibility() === Visibility::VISIBILITY_NOT_VISIBLE;
                    $siteId = $this->resolveSiteIdByStoreId((int)$storeId);

                    // Child action/forceIndexable must only reach the child's own rows; passing the
                    // parent id here would also match every sibling row via target_parent_id.
                    $this->baseProductObserver->execute(
                        [(int)$productId],
                        $nextAction,
                        $forceIndexable,
                        $siteId !== null ? [$siteId] : []
                    );
                    $this->updateParentEntity($product, (int)$storeId, $siteId);

                    $this->logger->debug(
                        '[InventoryUpdateObserver] Stock Update Store Check',
                        [
                            'product_id' => $productId,
                            'store_id' => $storeId,
                            'live_indexing' => $liveIndexing,
                            'action' => $nextAction
                        ]
                    );

                } catch (\Throwable $e) {
                    $this->logger->error(
                        '[InventoryUpdateObserver] Error processing stock for store ' . $storeId,
                        [
                            'product_id' => $productId,
                            'message' => $e->getMessage(),
                        ]
                    );
                    continue;
                }
            }

        } catch (\Throwable $e) {
            $this->logger->error(
                '[InventoryUpdateObserver] Exception thrown',
                [
                    'product_id' => $productId,
                    'message' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * Stock items saved through StockRegistry/StockItemRepository carry no orig data, and
     * dataHasChangedFor() loosely compares against null, so qty=0 / is_in_stock=0 (going OOS)
     * would read as "unchanged". Treat an untracked original value as changed.
     *
     * @param Item $stockItem
     * @param string $field
     * @return bool
     */
    private function hasStockFieldChanged(Item $stockItem, string $field): bool
    {
        $origData = $stockItem->getOrigData();
        if (!is_array($origData) || !array_key_exists($field, $origData)) {
            // Product saves re-save the stock item without orig data: compare with the stored row.
            return $this->stockItemSnapshot->hasChanged($stockItem, $field) ?? true;
        }

        return $stockItem->dataHasChangedFor($field);
    }

    /**
     * Re-queue the parent's own row so its availability is refreshed. A stock-only change
     * on a child never deletes the parent or forces it indexable; that is left to the
     * parent's own save/status flow.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param int $storeId
     * @param string|null $siteId
     * @return void
     */
    private function updateParentEntity(
        \Magento\Catalog\Api\Data\ProductInterface $product,
        int $storeId,
        ?string $siteId
    ): void {
        $productId = (int)$product->getId();
        $parentId = (int)$this->idProvider->getItemParentId($product);

        if ($parentId <= 0 || $parentId === $productId) {
            return;
        }

        $parentProduct = $this->productRepository->getById($parentId, false, $storeId, true);
        if ($this->productNextActionProvider->getNextActionByProduct($parentProduct, $storeId) !== Actions::UPSERT) {
            return;
        }

        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create()
            ->addFilter(IndexingEntity::TARGET_ID, $parentId);

        if ($siteId !== null) {
            $searchCriteriaBuilder->addFilter(IndexingEntity::SITE_ID, $siteId);
        }

        foreach ($this->indexingEntityRepository->getList($searchCriteriaBuilder->create())->getItems() as $indexingEntity) {
            if ($indexingEntity->getTargetParentId() !== null || !$indexingEntity->getIsIndexable()) {
                continue;
            }

            $indexingEntity->setNextAction(Actions::UPSERT);
            $this->indexingEntityRepository->save($indexingEntity);
        }
    }

    /**
     * @param int $storeId
     * @return string|null
     */
    private function resolveSiteIdByStoreId(int $storeId): ?string
    {
        $siteId = trim($this->configModel->getSiteIdByStoreId($storeId));

        return $siteId !== '' ? $siteId : null;
    }
}
