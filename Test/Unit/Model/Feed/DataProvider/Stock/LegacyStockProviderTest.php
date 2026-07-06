<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Stock;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\Context\StoreContextManager;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\LegacyStockProvider;
use Magento\CatalogInventory\Api\Data\StockItemCollectionInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;

class LegacyStockProviderTest extends TestCase
{
    private $legacyStockItemCriteriaFactoryMock;
    private $legacyStockItemRepositoryMock;
    private $stockConfigurationMock;
    private $storeContextManagerMock;
    private $loggerMock;
    private $legacyStockProvider;

    protected function setUp(): void
    {
        $this->legacyStockItemCriteriaFactoryMock = $this->createMock(StockItemCriteriaInterfaceFactory::class);
        $this->legacyStockItemRepositoryMock = $this->createMock(StockItemRepositoryInterface::class);
        $this->stockConfigurationMock = $this->createMock(StockConfigurationInterface::class);
        $this->storeContextManagerMock = $this->createMock(StoreContextManager::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->legacyStockProvider = new LegacyStockProvider(
            $this->legacyStockItemCriteriaFactoryMock,
            $this->legacyStockItemRepositoryMock,
            $this->stockConfigurationMock,
            $this->storeContextManagerMock,
            $this->loggerMock
        );
    }

    public function testGetStock(): void
    {
        $itemMock = $this->createMock(Item::class);
        $stockItemCollectionMock = $this->createMock(StockItemCollectionInterface::class);
        $criteriaMock = $this->createMock(StockItemCriteriaInterface::class);
        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->addMethods(['getStoreId'])
            ->getMock();
        $productIds = [1, 2, 3];

        $this->storeContextManagerMock->expects($this->once())
            ->method('getStoreFromContext')
            ->willReturn($storeMock);

        $storeMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn(0);

        $this->stockConfigurationMock->expects($this->once())
            ->method('getDefaultScopeId')
            ->willReturn(0);

        $this->legacyStockItemCriteriaFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($criteriaMock);

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
            ->willReturn([$itemMock]);

        $itemMock->expects($this->once())
            ->method('setStoreId')
            ->with(0)
            ->willReturnSelf();

        $itemMock->expects($this->once())
            ->method('getProductId')
            ->willReturn(1);

        $itemMock->expects($this->once())
            ->method('getQty')
            ->willReturn(3.0);

        $itemMock->expects($this->once())
            ->method('getIsInStock')
            ->willReturn(true);

        $itemMock->expects($this->once())
            ->method('getManageStock')
            ->willReturn(true);

        $this->assertSame(
            [
                1 => [
                    'qty' => 3.0,
                    'in_stock' => true,
                    'is_stock_managed' => true,
                ],
            ],
            $this->legacyStockProvider->getStock($productIds)
        );
    }
}
