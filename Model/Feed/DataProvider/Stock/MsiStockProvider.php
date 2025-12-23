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

use AthosCommerce\Feed\Model\Feed\Context\StoreContextManager;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface as MsiStockResolverInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Framework\Filesystem\DirectoryList;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class MsiStockProvider implements StockProviderInterface
{
    /**
     * @var StoreContextManager
     */
    private $storeContextManager;
    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;
    /**
     * @var MsiStockResolverInterface
     */
    private $stockResolver;
    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;
    /**
     * @var Product
     */
    private $productResource;
    /**
     * @var GetReservationsQuantityInterface
     */
    private $getReservationsQuantity;
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
     * @var Type
     */
    private $typeManager;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * MsiStockProvider constructor.
     *
     * @param StoreContextManager $storeContextManager
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param Product $productResource
     * @param StockItemCriteriaInterfaceFactory $legacyStockItemCriteriaFactory
     * @param StockItemRepositoryInterface $legacyStockItemRepository
     * @param StockConfigurationInterface $stockConfiguration
     * @param Type $typeManager
     * @param GetReservationsQuantityInterface $getReservationsQuantity
     * @param MsiStockResolverInterface $stockResolver
     * @param GetStockItemDataInterface $getStockItemData
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        StoreContextManager $storeContextManager,
        WebsiteRepositoryInterface $websiteRepository,
        Product $productResource,
        StockItemCriteriaInterfaceFactory $legacyStockItemCriteriaFactory,
        StockItemRepositoryInterface $legacyStockItemRepository,
        StockConfigurationInterface $stockConfiguration,
        Type $typeManager,
        GetReservationsQuantityInterface $getReservationsQuantity,
        MsiStockResolverInterface $stockResolver,
        GetStockItemDataInterface $getStockItemData,
        AthosCommerceLogger $logger,
    ) {
        $this->storeContextManager = $storeContextManager;
        $this->websiteRepository = $websiteRepository;
        $this->productResource = $productResource;
        $this->legacyStockItemCriteriaFactory = $legacyStockItemCriteriaFactory;
        $this->legacyStockItemRepository = $legacyStockItemRepository;
        $this->stockConfiguration = $stockConfiguration;
        $this->typeManager = $typeManager;
        $this->getReservationsQuantity = $getReservationsQuantity;
        $this->stockResolver = $stockResolver;
        $this->getStockItemData = $getStockItemData;
        $this->logger = $logger;
    }

    /**
     * [
     *      product_id => [
     *          'qty' => float,
     *          'in_stock' => bool,
     *          'is_stock_managed' => bool
     *      ],
     *      .........
     * ]
     *
     * @param array $productIds
     *
     * @return array
     */
    public function getStock(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }
        $store = $this->storeContextManager->getStoreFromContext();
        $storeId = (int)$store->getStoreId();
        $websiteId = $store->getWebsiteId();
        $website = $this->websiteRepository->getById($websiteId);
        $stock = $this->stockResolver->execute(
            SalesChannelInterface::TYPE_WEBSITE,
            $website->getCode()
        );
        $stockId = $stock->getStockId();
        $skus = $this->getSkus($productIds);
        $configurations = $this->getItemConfigurations($productIds);
        $result = [];
        foreach ($productIds as $productId) {
            $sku = $skus[$productId] ?? null;
            if (!$sku) {
                continue;
            }

            $sku = (string)$sku;
            try {
                $stockData = $this->getStockItemData->execute($sku, $stockId) ?? [];
                $reservation = $this->getReservationsQuantity->execute($sku, $stockId);
            } catch (\Exception $exception) {
                $this->logger->error(
                    "Error processing stock data for SKU: {$sku}",
                    [
                        'method' => __METHOD__,
                        'productId' => $productId,
                        'stockId' => $stockId,
                        'storeId' => $storeId,
                        'exception' => $exception,
                    ]
                );
                continue;
            }

            /** @var Item $configuration */
            $configuration = $configurations[$productId] ?? null;
            $configuration->setStoreId($storeId);
            $result[$productId] = [
                'qty' => $this->getQty($stockData, $reservation),
                'in_stock' => $this->getIsInStock($stockData, $reservation, $configuration),
                'is_stock_managed' => $configuration->getManageStock(),
            ];
        }

        return $result;
    }

    /**
     * @param array $stockData
     * @param float $reservation
     *
     * @return float
     */
    private function getQty(array $stockData, float $reservation): float
    {
        if (!isset($stockData[GetStockItemDataInterface::QUANTITY])) {
            return 0;
        }

        return $stockData[GetStockItemDataInterface::QUANTITY] + $reservation;
    }

    /**
     * @param array $stockData
     * @param float $reservation
     * @param StockItemInterface|null $configuration
     *
     * @return bool
     */
    private function getIsInStock(
        array $stockData,
        float $reservation,
        ?StockItemInterface $configuration = null
    ): bool {
        if (!$configuration) {
            return false;
        }

        if (!$configuration->getManageStock()) {
            return true;
        }

        $isSalable = $stockData['is_salable'] ?? null;
        // composite products (configurable, grouped, bundle) always have 0 qty
        if (!is_null($isSalable) && in_array($configuration->getTypeId(), $this->typeManager->getCompositeTypes())) {
            return (bool)$isSalable;
        }

        if (!is_null($isSalable) && $isSalable == 0) {
            return false;
        }

        return $this->getQty($stockData, $reservation) > $configuration->getMinQty();
    }

    /**
     * @param array $productIds
     *
     * @return StockItemInterface[]
     */
    private function getItemConfigurations(array $productIds): array
    {
        $searchCriteria = $this->legacyStockItemCriteriaFactory->create();
        $searchCriteria->setScopeFilter($this->stockConfiguration->getDefaultScopeId());
        $searchCriteria->setProductsFilter($productIds);
        $items = $this->legacyStockItemRepository->getList($searchCriteria)->getItems();
        $result = [];
        foreach ($items as $item) {
            $result[$item->getProductId()] = $item;
        }

        return $result;
    }

    /**
     * @param array $productIds
     *
     * @return array
     */
    private function getSkus(array $productIds): array
    {
        $skus = $this->productResource->getProductsSku($productIds);
        $result = [];
        foreach ($skus as $skuData) {
            $result[$skuData['entity_id']] = $skuData['sku'];
        }

        return $result;
    }
}
