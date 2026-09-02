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

namespace AthosCommerce\Feed\Test\Unit\Helper;

require_once dirname(__DIR__) . '/_files/SalesDataInterfaceFactory.php';
//TODO: Check
require_once __DIR__ . '/../_files/bootstrap-stubs.php';

use AthosCommerce\Feed\Api\Data\SalesDataInterface;
use AthosCommerce\Feed\Api\Data\SalesDataInterfaceFactory;
use AthosCommerce\Feed\Helper\Sale;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;
use Magento\Store\Model\StoresConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SaleTest extends TestCase
{
    /**
     * @var StoresConfig|MockObject
     */
    private $storesConfigMock;

    protected function setUp(): void
    {
        $this->storesConfigMock = $this->createMock(StoresConfig::class);
    }

    public function testGetSalesMapsCollectionItems(): void
    {
        $selectMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['where', 'joinLeft', 'order', 'limit'])
            ->getMock();
        $selectMock->expects($this->once())->method('where');
        $selectMock->expects($this->once())
            ->method('joinLeft')
            ->with(
                ['order_table' => 'sales_order'],
                'order_table.entity_id = main_table.order_id',
                ['order_customer_id' => 'customer_id', 'order_customer_email' => 'customer_email']
            )
            ->willReturnSelf();
        $selectMock->expects($this->once())
            ->method('order')
            ->with('main_table.item_id ASC')
            ->willReturnSelf();
        $selectMock->expects($this->once())
            ->method('limit')
            ->with(10, 0)
            ->willReturnSelf();

        $collectionBaseMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSelect', 'addBindParam', 'getTable', 'getItems'])
            ->getMock();
        $collectionBaseMock->expects($this->exactly(4))->method('getSelect')->willReturn($selectMock);
        $collectionBaseMock->expects($this->once())->method('addBindParam')->with(':from', '2000-01-01');
        $collectionBaseMock->expects($this->once())->method('getTable')->with('sales_order')->willReturn('sales_order');

        $itemMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getData'])
            ->getMock();
        $itemMock->method('getData')
            ->willReturnMap([
                ['order_id', 123],
                ['order_customer_id', null],
                ['order_customer_email', 'guest@example.com'],
                ['product_id', 456],
                ['qty_ordered', 5],
                ['qty_canceled', 1],
                ['qty_refunded', 1],
                ['price', 99.99],
                ['store_id', ''],
                ['created_at', '2026-07-06 10:00:00'],
            ]);
        $collectionBaseMock->expects($this->once())->method('getItems')->willReturn([$itemMock]);

        $salesDataMock = $this->createMock(SalesDataInterface::class);
        $salesDataMock->expects($this->once())->method('setOrderId')->with('123');
        $salesDataMock->expects($this->once())->method('setCustomerId')->with('guest@example.com');
        $salesDataMock->expects($this->once())->method('setProductId')->with('456');
        $salesDataMock->expects($this->once())->method('setQuantity')->with('3');
        $salesDataMock->expects($this->once())->method('setPrice')->with('99.99');
        $salesDataMock->expects($this->once())
            ->method('setCreatedAt')
            ->with($this->matchesRegularExpression('/^2026-07-06 10:00:00[+\-]\d{2}:\d{2}$/'));

        $saleFactoryMock = $this->createMock(CollectionFactory::class);
        $saleFactoryMock->expects($this->once())->method('create')->willReturn($collectionBaseMock);

        $saleHelper = new Sale(
            $this->storesConfigMock,
            $saleFactoryMock,
            new SalesDataInterfaceFactory($salesDataMock)
        );
        $result = $saleHelper->getSales('2000-01-01', '1,10');

        $this->assertCount(1, $result);
        $this->assertSame($salesDataMock, $result[0]);
    }

    public function testGetSalesUsesStoreTimezoneWhenStoreIdExists(): void
    {
        $selectMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['where', 'joinLeft', 'order', 'limit'])
            ->getMock();
        $selectMock->expects($this->once())->method('where');
        $selectMock->expects($this->once())->method('joinLeft')->willReturnSelf();
        $selectMock->expects($this->once())->method('order')->with('main_table.item_id ASC')->willReturnSelf();
        $selectMock->expects($this->once())->method('limit')->with(10, 0)->willReturnSelf();

        $collectionBaseMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSelect', 'addBindParam', 'getTable', 'getItems'])
            ->getMock();
        $collectionBaseMock->expects($this->exactly(4))->method('getSelect')->willReturn($selectMock);
        $collectionBaseMock->expects($this->once())->method('addBindParam')->with(':from', '2000-01-01');
        $collectionBaseMock->expects($this->once())->method('getTable')->with('sales_order')->willReturn('sales_order');

        $itemMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getData'])
            ->getMock();
        $itemMock->method('getData')
            ->willReturnMap([
                ['order_id', 321],
                ['order_customer_id', 99],
                ['order_customer_email', 'fallback@example.com'],
                ['product_id', 654],
                ['qty_ordered', 2],
                ['qty_canceled', 0],
                ['qty_refunded', 0],
                ['price', 55.00],
                ['store_id', '0'],
                ['created_at', '2026-07-06 10:00:00'],
            ]);
        $collectionBaseMock->expects($this->once())->method('getItems')->willReturn([$itemMock]);

        $this->storesConfigMock->expects($this->once())
            ->method('getStoresConfigByPath')
            ->willReturn(['0' => 'UTC']);

        $salesDataMock = $this->createMock(SalesDataInterface::class);
        $salesDataMock->expects($this->once())->method('setCustomerId')->with('99');
        $salesDataMock->expects($this->once())->method('setCreatedAt')->with('2026-07-06 10:00:00+00:00');

        $saleFactoryMock = $this->createMock(CollectionFactory::class);
        $saleFactoryMock->expects($this->once())->method('create')->willReturn($collectionBaseMock);

        $saleHelper = new Sale(
            $this->storesConfigMock,
            $saleFactoryMock,
            new SalesDataInterfaceFactory($salesDataMock)
        );
        $saleHelper->getSales('2000-01-01', '1,10');

        $this->addToAssertionCount(1);
    }

    public function testGetSalesTotalCountAppliesDateRangeAndReturnsSize(): void
    {
        $selectMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['where'])
            ->getMock();
        $selectMock->expects($this->once())->method('where');

        $collectionBaseMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSelect', 'addBindParam', 'getSize'])
            ->getMock();
        $collectionBaseMock->expects($this->once())->method('getSelect')->willReturn($selectMock);
        $collectionBaseMock->expects($this->once())->method('addBindParam')->with(':from', '2000-01-01');
        $collectionBaseMock->expects($this->once())->method('getSize')->willReturn(42);

        $saleFactoryMock = $this->createMock(CollectionFactory::class);
        $saleFactoryMock->expects($this->once())->method('create')->willReturn($collectionBaseMock);

        $saleHelper = new Sale(
            $this->storesConfigMock,
            $saleFactoryMock,
            new SalesDataInterfaceFactory()
        );

        $this->assertSame(42, $saleHelper->getSalesTotalCount('2000-01-01'));
    }
}
