<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Stock;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\Context\StoreContextManager;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\MsiStockProvider;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\CatalogInventory\Api\Data\StockItemCollectionInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface as MsiStockResolverInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;

class MsiStockProviderTest extends TestCase
{
    private $storeContextManagerMock;
    private $websiteRepositoryMock;
    private $productResourceMock;
    private $legacyStockItemCriteriaFactoryMock;
    private $legacyStockItemRepositoryMock;
    private $stockConfigurationMock;
    private $typeManagerMock;
    private $getReservationsQuantityMock;
    private $stockResolverMock;
    private $getStockItemDataMock;
    private $loggerMock;
    private $msiStockProvider;

    protected function setUp(): void
    {
        $this->storeContextManagerMock = $this->createMock(StoreContextManager::class);
        $this->websiteRepositoryMock = $this->createMock(WebsiteRepositoryInterface::class);
        $this->productResourceMock = $this->createMock(Product::class);
        $this->legacyStockItemCriteriaFactoryMock = $this->createMock(StockItemCriteriaInterfaceFactory::class);
        $this->legacyStockItemRepositoryMock = $this->createMock(StockItemRepositoryInterface::class);
        $this->stockConfigurationMock = $this->createMock(StockConfigurationInterface::class);
        $this->typeManagerMock = $this->createMock(Type::class);
        $this->getReservationsQuantityMock = $this->createMock(GetReservationsQuantityInterface::class);
        $this->stockResolverMock = $this->createMock(MsiStockResolverInterface::class);
        $this->getStockItemDataMock = $this->createMock(GetStockItemDataInterface::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->msiStockProvider = new MsiStockProvider(
            $this->storeContextManagerMock,
            $this->websiteRepositoryMock,
            $this->productResourceMock,
            $this->legacyStockItemCriteriaFactoryMock,
            $this->legacyStockItemRepositoryMock,
            $this->stockConfigurationMock,
            $this->typeManagerMock,
            $this->getReservationsQuantityMock,
            $this->stockResolverMock,
            $this->getStockItemDataMock,
            $this->loggerMock
        );
    }

    public function testGetStock(): void
    {
        $productIds = [1, 2, 3];
        $reservationFirst = 1.0;
        $reservationSecond = 2.0;
        $reservationThird = 3.0;

        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->addMethods(['getStoreId'])
            ->onlyMethods(['getWebsiteId'])
            ->getMock();

        $websiteMock = $this->createMock(WebsiteInterface::class);
        $stockMock = $this->createMock(StockInterface::class);
        $stockItemCollectionMock = $this->createMock(StockItemCollectionInterface::class);
        $criteriaMock = $this->createMock(StockItemCriteriaInterface::class);

        $itemMock = $this->createMock(Item::class);

        $itemMockSecond = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getTypeId'])
            ->onlyMethods(['getProductId','setStoreId','getManageStock','getMinQty'])
            ->getMock();

        $itemMockThird = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getTypeId'])
            ->onlyMethods(['getProductId','setStoreId','getManageStock','getMinQty'])
            ->getMock();

        $this->storeContextManagerMock->expects($this->once())
            ->method('getStoreFromContext')
            ->willReturn($storeMock);

        $storeMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn(1);

        $storeMock->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn(1);

        $this->websiteRepositoryMock->expects($this->once())
            ->method('getById')
            ->with(1)
            ->willReturn($websiteMock);

        $websiteMock->expects($this->once())
            ->method('getCode')
            ->willReturn('default');

        $this->stockResolverMock->expects($this->once())
            ->method('execute')
            ->with(SalesChannelInterface::TYPE_WEBSITE, 'default')
            ->willReturn($stockMock);

        $stockMock->expects($this->once())
            ->method('getStockId')
            ->willReturn(1);

        $this->productResourceMock->expects($this->once())
            ->method('getProductsSku')
            ->with($productIds)
            ->willReturn([
                ['entity_id' => 1, 'sku' => '1'],
                ['entity_id' => 2, 'sku' => '2'],
                ['entity_id' => 3, 'sku' => '3'],
            ]);

        $this->legacyStockItemCriteriaFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($criteriaMock);

        $this->stockConfigurationMock->expects($this->once())
            ->method('getDefaultScopeId')
            ->willReturn(0);

        $criteriaMock->expects($this->once())
            ->method('setScopeFilter')
            ->with(0)
            ->willReturnSelf();

        $criteriaMock->expects($this->once())
            ->method('setProductsFilter')
            ->with($productIds)
            ->willReturnSelf();

        $this->legacyStockItemRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($criteriaMock)
            ->willReturn($stockItemCollectionMock);

        $stockItemCollectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$itemMock, $itemMockSecond, $itemMockThird]);

        $itemMock->method('getProductId')->willReturn(1);
        $itemMockSecond->method('getProductId')->willReturn(2);
        $itemMockThird->method('getProductId')->willReturn(3);

        $this->getStockItemDataMock->method('execute')
            ->willReturnMap([
                ['1', 1, ['quantity' => 1, 'is_salable' => true]],
                ['2', 1, ['quantity' => 2, 'is_salable' => true]],
                ['3', 1, ['quantity' => 3, 'is_salable' => true]],
            ]);

        $this->getReservationsQuantityMock->method('execute')
            ->willReturnMap([
                ['1', 1, $reservationFirst],
                ['2', 1, $reservationSecond],
                ['3', 1, $reservationThird],
            ]);

        $itemMock->expects($this->once())->method('setStoreId')->with(1);
        $itemMockSecond->expects($this->once())->method('setStoreId')->with(1);
        $itemMockThird->expects($this->once())->method('setStoreId')->with(1);

        $itemMock->method('getManageStock')->willReturn(false);

        $itemMockSecond->method('getManageStock')->willReturn(true);
        $itemMockSecond->method('getTypeId')->willReturn('configurable');

        $itemMockThird->method('getManageStock')->willReturn(true);
        $itemMockThird->method('getTypeId')->willReturn('simple');
        $itemMockThird->method('getMinQty')->willReturn(13.0);

        $this->typeManagerMock->method('getCompositeTypes')
            ->willReturn(['configurable', 'bundle']);

        $this->assertSame(
            [
                1 => [
                    'qty' => 2.0,
                    'in_stock' => true,
                    'is_stock_managed' => false,
                ],
                2 => [
                    'qty' => 4.0,
                    'in_stock' => true,
                    'is_stock_managed' => true,
                ],
                3 => [
                    'qty' => 6.0,
                    'in_stock' => false,
                    'is_stock_managed' => true,
                ],
            ],
            $this->msiStockProvider->getStock($productIds)
        );
    }

    public function testGetStockExceptionCase(): void
    {
        $productIds = [1];

        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->addMethods(['getStoreId'])
            ->onlyMethods(['getWebsiteId'])
            ->getMock();

        $websiteMock = $this->createMock(WebsiteInterface::class);
        $stockMock = $this->createMock(StockInterface::class);
        $stockItemCollectionMock = $this->createMock(StockItemCollectionInterface::class);
        $criteriaMock = $this->createMock(StockItemCriteriaInterface::class);
        $itemMock = $this->createMock(Item::class);

        $this->storeContextManagerMock->expects($this->once())
            ->method('getStoreFromContext')
            ->willReturn($storeMock);

        $storeMock->method('getStoreId')->willReturn(1);
        $storeMock->method('getWebsiteId')->willReturn(1);

        $this->websiteRepositoryMock->expects($this->once())
            ->method('getById')
            ->with(1)
            ->willReturn($websiteMock);

        $websiteMock->expects($this->once())
            ->method('getCode')
            ->willReturn('default');

        $this->stockResolverMock->expects($this->once())
            ->method('execute')
            ->willReturn($stockMock);

        $stockMock->expects($this->once())
            ->method('getStockId')
            ->willReturn(1);

        $this->productResourceMock->expects($this->once())
            ->method('getProductsSku')
            ->with($productIds)
            ->willReturn([
                ['entity_id' => 1, 'sku' => '1'],
            ]);

        $this->legacyStockItemCriteriaFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($criteriaMock);

        $this->stockConfigurationMock->expects($this->once())
            ->method('getDefaultScopeId')
            ->willReturn(0);

        $criteriaMock->method('setScopeFilter')->willReturnSelf();
        $criteriaMock->method('setProductsFilter')->willReturnSelf();

        $this->legacyStockItemRepositoryMock->expects($this->once())
            ->method('getList')
            ->willReturn($stockItemCollectionMock);

        $stockItemCollectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$itemMock]);

        $itemMock->method('getProductId')->willReturn(1);

        $this->getStockItemDataMock->expects($this->once())
            ->method('execute')
            ->with('1', 1)
            ->willThrowException(new \Exception('stock error'));

        $this->loggerMock->expects($this->once())
            ->method('error');

        $this->assertSame([], $this->msiStockProvider->getStock($productIds));
    }
}
