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
use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\WebsiteRepositoryInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class MsiStockProvider implements StockProviderInterface
{
    private const MSI_STOCK_RESOLVER_INTERFACE = \Magento\InventorySalesApi\Api\StockResolverInterface::class;
    private const MSI_STOCK_ITEM_DATA_INTERFACE = \Magento\InventorySalesApi\Model\GetStockItemDataInterface::class;
    private const MSI_RES_QTY_INTERFACE = \Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface::class;
    private const SALES_CHANNEL_WEBSITE_TYPE = 'website';
    private const STOCK_QUANTITY_KEY = 'quantity';

    /**
     * @var StoreContextManager
     */
    private $storeContextManager;
    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;
    /**
     * @var Product
     */
    private $productResource;
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
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var array<string, object>
     */
    private $msiServiceCache = [];

    /**
     * @param StoreContextManager $storeContextManager
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param Product $productResource
     * @param StockItemCriteriaInterfaceFactory $legacyStockItemCriteriaFactory
     * @param StockItemRepositoryInterface $legacyStockItemRepository
     * @param StockConfigurationInterface $stockConfiguration
     * @param Type $typeManager
     * @param AthosCommerceLogger $logger
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        StoreContextManager $storeContextManager,
        WebsiteRepositoryInterface $websiteRepository,
        Product $productResource,
        StockItemCriteriaInterfaceFactory $legacyStockItemCriteriaFactory,
        StockItemRepositoryInterface $legacyStockItemRepository,
        StockConfigurationInterface $stockConfiguration,
        Type $typeManager,
        AthosCommerceLogger $logger,
        ObjectManagerInterface $objectManager
    ) {
        $this->storeContextManager = $storeContextManager;
        $this->websiteRepository = $websiteRepository;
        $this->productResource = $productResource;
        $this->legacyStockItemCriteriaFactory = $legacyStockItemCriteriaFactory;
        $this->legacyStockItemRepository = $legacyStockItemRepository;
        $this->stockConfiguration = $stockConfiguration;
        $this->typeManager = $typeManager;
        $this->logger = $logger;
        $this->objectManager = $objectManager;
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
        $stockResolver = $this->getMsiService(self::MSI_STOCK_RESOLVER_INTERFACE);
        $getStockItemData = $this->getMsiService(self::MSI_STOCK_ITEM_DATA_INTERFACE);
        $getReservationsQuantity = $this->getMsiService(self::MSI_RES_QTY_INTERFACE);
        $stock = $stockResolver->execute(self::SALES_CHANNEL_WEBSITE_TYPE, $website->getCode());
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
                $stockData = $getStockItemData->execute($sku, $stockId) ?? [];
                $reservation = $getReservationsQuantity->execute($sku, $stockId);
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
     * Retrieve product quantity
     *
     * @param array $stockData
     * @param float $reservation
     *
     * @return float
     */
    private function getQty(array $stockData, float $reservation): float
    {
        if (!isset($stockData[self::STOCK_QUANTITY_KEY])) {
            return 0;
        }

        return $stockData[self::STOCK_QUANTITY_KEY] + $reservation;
    }

    /**
     * Determine if the product is in stock
     *
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
        if ($isSalable !== null && in_array($configuration->getTypeId(), $this->typeManager->getCompositeTypes())) {
            return (bool)$isSalable;
        }

        if ($isSalable !== null && $isSalable == 0) {
            return false;
        }

        return $this->getQty($stockData, $reservation) > $configuration->getMinQty();
    }

    /**
     * Retrieve item configurations by product IDs
     *
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
     * Retrieve product SKUs by product IDs
     *
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

    /**
     * Retrieve MSI service instance
     *
     * @param string $type
     *
     * @return object
     * @throws NoSuchEntityException
     */
    private function getMsiService(string $type): object
    {
        if (isset($this->msiServiceCache[$type])) {
            return $this->msiServiceCache[$type];
        }

        if (!interface_exists($type) && !class_exists($type)) {
            throw new NoSuchEntityException(__('MSI dependency is not available: %1', $type));
        }

        $this->msiServiceCache[$type] = $this->objectManager->get($type);
        return $this->msiServiceCache[$type];
    }
}
