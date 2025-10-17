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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Stock;

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
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface as MsiStockResolverInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\MsiStockProvider;

class MsiStockProviderTest extends \PHPUnit\Framework\TestCase
{
    private $storeManagerMock;

    private $websiteRepositoryMock;

    private $productResourceMock;

    private $legacyStockItemCriteriaFactoryMock;

    private $legacyStockItemRepositoryMock;

    private $stockConfigurationMock;

    private $typeManagerMock;

    /**
     * @var GetReservationsQuantityInterface
     */
    private $getReservationsQuantityMock;

    /**
     * @var MsiStockResolverInterface
     */
    private $stockResolverMock;

    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemDataMock;
    private $msiStockProvider;

    public function setUp(): void
    {
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->websiteRepositoryMock = $this->createMock(WebsiteRepositoryInterface::class);
        $this->productResourceMock = $this->createMock(Product::class);
        $this->legacyStockItemCriteriaFactoryMock = $this->createMock(StockItemCriteriaInterfaceFactory::class);
        $this->legacyStockItemRepositoryMock = $this->createMock(StockItemRepositoryInterface::class);
        $this->stockConfigurationMock = $this->createMock(StockConfigurationInterface::class);
        $this->typeManagerMock = $this->createMock(Type::class);
        $this->getReservationsQuantityMock = $this->createMock(GetReservationsQuantityInterface::class);
        $this->stockResolverMock = $this->createMock(MsiStockResolverInterface::class);
        $this->getStockItemDataMock = $this->createMock(GetStockItemDataInterface::class);
        $this->msiStockProvider = new MsiStockProvider(
            $this->storeManagerMock,
            $this->websiteRepositoryMock,
            $this->productResourceMock,
            $this->legacyStockItemCriteriaFactoryMock,
            $this->legacyStockItemRepositoryMock,
            $this->stockConfigurationMock,
            $this->typeManagerMock,
            $this->getReservationsQuantityMock,
            $this->stockResolverMock,
            $this->getStockItemDataMock
        );
    }

    public function testGetStock()
    {
        $reservationFirst = (float)rand(0, 10);
        $reservationSecond = (float)rand(0, 10);
        $reservationThird = (float)rand(0, 10);
        $getReservationsQuantityMock = $this->createMock(GetReservationsQuantityInterface::class);
        $stockResolverMock = $this->createMock(StockResolverInterface::class);
        $getStockItemDataMock = $this->createMock(GetStockItemDataInterface::class);
        $itemMock = $this->createMock(Item::class);
        $itemMockSecond = $this->createMock(Item::class);
        $itemMockThird = $this->createMock(Item::class);
        $itemsMock = [$itemMock, $itemMockSecond, $itemMockThird];
        $productIds = [1,2,3];
        $websiteMock = $this->getMockForAbstractClass(WebsiteInterface::class);
        $storeMock = $this->getMockForAbstractClass(StoreInterface::class);
        $stockMock = $this->createMock(StockInterface::class);
        $stockItemCollectionMock = $this->getMockForAbstractClass(StockItemCollectionInterface::class);
        $legacyStockInterfaceMock = $this->getMockForAbstractClass(StockItemCriteriaInterface::class);

        $msiStockProvider = new \ReflectionClass(MsiStockProvider::class);
        $getReservationsQuantity = $msiStockProvider->getProperty('getReservationsQuantity');
        $getReservationsQuantity->setAccessible(true);
        $getReservationsQuantity->setValue($this->msiStockProvider, $getReservationsQuantityMock);
        $stockResolver = $msiStockProvider->getProperty('stockResolver');
        $stockResolver->setAccessible(true);
        $stockResolver->setValue($this->msiStockProvider, $stockResolverMock);
        $getStockItemData = $msiStockProvider->getProperty('getStockItemData');
        $getStockItemData->setAccessible(true);
        $getStockItemData->setValue($this->msiStockProvider, $getStockItemDataMock);

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
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
        $stockResolverMock->expects($this->once())
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
                [
                    'entity_id' => 1,
                    'sku' => '1',
                ],
                [
                    'entity_id' => 2,
                    'sku' => '2',
                ],
                [
                    'entity_id' => 3,
                    'sku' => '3',
                ],
            ]);

        $this->legacyStockItemCriteriaFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($legacyStockInterfaceMock);
        $legacyStockInterfaceMock->expects($this->once())
            ->method('setScopeFilter')
            ->willReturnSelf();
        $legacyStockInterfaceMock->expects($this->once())
            ->method('setProductsFilter')
            ->with($productIds)
            ->willReturnSelf();
        $this->legacyStockItemRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($legacyStockInterfaceMock)
            ->willReturn($stockItemCollectionMock);
        $stockItemCollectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn($itemsMock);
        $itemMock->expects($this->once())
            ->method('getProductId')
            ->willReturn(1);
        $itemMockSecond->expects($this->once())
            ->method('getProductId')
            ->willReturn(2);
        $itemMockThird->expects($this->once())
            ->method('getProductId')
            ->willReturn(3);

        $getStockItemDataMock->expects($this->at(0))
            ->method('execute')
            ->with('1', 1)
            ->willReturn([
                'quantity' => 1,
                'is_salable' => true
            ]);
        $getStockItemDataMock->expects($this->at(1))
            ->method('execute')
            ->with('2', 1)
            ->willReturn([
                'quantity' => 2,
                'is_salable' => true
            ]);
        $getStockItemDataMock->expects($this->at(2))
            ->method('execute')
            ->with('3', 1)
            ->willReturn([
                'quantity' => 3,
                'is_salable' => true
            ]);

        $getReservationsQuantityMock->expects($this->at(0))
            ->method('execute')
            ->with('1', 1)
            ->willReturn($reservationFirst);
        $itemMock->expects($this->once())
            ->method('setStoreId')
            ->with(1);
        $itemMock->expects($this->any())
            ->method('getManageStock')
            ->willReturn(false);
        $getReservationsQuantityMock->expects($this->at(1))
            ->method('execute')
            ->with('2', 1)
            ->willReturn($reservationSecond);
        $itemMockSecond->expects($this->once())
            ->method('setStoreId')
            ->with(1);
        $itemMockSecond->expects($this->any())
            ->method('getManageStock')
            ->willReturn(true);
        $itemMockSecond->expects($this->once())
            ->method('__call')
            ->with('getTypeId')
            ->willReturn('configurable');
        $this->typeManagerMock->expects($this->any())
            ->method('getCompositeTypes')
            ->willReturn(['configurable', 'bundle']);
        $getReservationsQuantityMock->expects($this->at(2))
            ->method('execute')
            ->with('3', 1)
            ->willReturn($reservationThird);
        $itemMockThird->expects($this->once())
            ->method('setStoreId')
            ->with(1);
        $itemMockThird->expects($this->any())
            ->method('getManageStock')
            ->willReturn(true);
        $itemMockThird->expects($this->once())
            ->method('__call')
            ->with('getTypeId')
            ->willReturn('simple');
        $itemMockThird->expects($this->once())
            ->method('getMinQty')
            ->willReturn(13);

        $this->assertSame(
            [
                1 => [
                    'qty' => 1 + $reservationFirst,
                    'in_stock' => true,
                    'is_stock_managed' => false
                ],
                2 => [
                    'qty' => 2 + $reservationSecond,
                    'in_stock' => true,
                    'is_stock_managed' =>true
                ],
                3 => [
                    'qty' => 3 + $reservationThird,
                    'in_stock' => false,
                    'is_stock_managed' =>true
                ]
            ],
            $this->msiStockProvider->getStock($productIds, 1)
        );
    }

    public function testGetStockExceptionCase()
    {
        $getReservationsQuantityMock = $this->createMock(GetReservationsQuantityInterface::class);
        $stockResolverMock = $this->createMock(StockResolverInterface::class);
        $getStockItemDataMock = $this->createMock(GetStockItemDataInterface::class);
        $itemMock = $this->createMock(Item::class);
        $itemMockSecond = $this->createMock(Item::class);
        $itemMockThird = $this->createMock(Item::class);
        $itemsMock = [$itemMock, $itemMockSecond, $itemMockThird];
        $productIds = [1,2,3];
        $websiteMock = $this->getMockForAbstractClass(WebsiteInterface::class);
        $storeMock = $this->getMockForAbstractClass(StoreInterface::class);
        $stockMock = $this->createMock(StockInterface::class);
        $stockItemCollectionMock = $this->getMockForAbstractClass(StockItemCollectionInterface::class);
        $legacyStockInterfaceMock = $this->getMockForAbstractClass(StockItemCriteriaInterface::class);

        $msiStockProvider = new \ReflectionClass(MsiStockProvider::class);
        $getReservationsQuantity = $msiStockProvider->getProperty('getReservationsQuantity');
        $getReservationsQuantity->setAccessible(true);
        $getReservationsQuantity->setValue($this->msiStockProvider, $getReservationsQuantityMock);
        $stockResolver = $msiStockProvider->getProperty('stockResolver');
        $stockResolver->setAccessible(true);
        $stockResolver->setValue($this->msiStockProvider, $stockResolverMock);
        $getStockItemData = $msiStockProvider->getProperty('getStockItemData');
        $getStockItemData->setAccessible(true);
        $getStockItemData->setValue($this->msiStockProvider, $getStockItemDataMock);

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
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
        $stockResolverMock->expects($this->once())
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
                [
                    'entity_id' => 1,
                    'sku' => '1',
                ],
                [
                    'entity_id' => 2,
                    'sku' => '2',
                ],
                [
                    'entity_id' => 3,
                    'sku' => '3',
                ],
            ]);

        $this->legacyStockItemCriteriaFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($legacyStockInterfaceMock);
        $legacyStockInterfaceMock->expects($this->once())
            ->method('setScopeFilter')
            ->willReturnSelf();
        $legacyStockInterfaceMock->expects($this->once())
            ->method('setProductsFilter')
            ->with($productIds)
            ->willReturnSelf();
        $this->legacyStockItemRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($legacyStockInterfaceMock)
            ->willReturn($stockItemCollectionMock);
        $stockItemCollectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn($itemsMock);
        $itemMock->expects($this->once())
            ->method('getProductId')
            ->willReturn(1);
        $itemMockSecond->expects($this->once())
            ->method('getProductId')
            ->willReturn(2);
        $itemMockThird->expects($this->once())
            ->method('getProductId')
            ->willReturn(3);

        $getStockItemDataMock->expects($this->any())
            ->method('execute')
            ->with('1', 1)
            ->willThrowException(new \Exception());

        $this->assertSame(
            [],
            $this->msiStockProvider->getStock($productIds, 1)
        );
    }
}
