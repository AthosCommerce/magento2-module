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

use AthosCommerce\Feed\Api\Data\SalesDataInterface;
use AthosCommerce\Feed\Api\Data\SalesDataInterfaceFactory;
use AthosCommerce\Feed\Helper\Sale;
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

    /**
     * @var SalesDataInterfaceFactory|MockObject
     */
    private $salesDataFactoryMock;

    protected function setUp(): void
    {
        $this->storesConfigMock = $this->createMock(StoresConfig::class);
        $this->salesDataFactoryMock = $this->createMock(SalesDataInterfaceFactory::class);
    }

    public function testGetSalesMapsCollectionItems(): void
    {
        $selectMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['where', 'limit'])
            ->getMock();

        $selectMock->expects($this->never())->method('where');
        $selectMock->expects($this->never())->method('limit');

        $collectionBaseMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getSelect', 'addBindParam'])
            ->getMock();

        $collectionBaseMock->expects($this->once())
            ->method('getSelect')
            ->willReturn($selectMock);

        $collectionBaseMock->expects($this->never())
            ->method('addBindParam');

        $orderMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getData'])
            ->getMock();

        $itemMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getOrderID', 'getOrder', 'getData'])
            ->getMock();

        $salesDataMock = $this->createMock(SalesDataInterface::class);

        $orderMock->method('getData')
            ->willReturnMap([
                ['customer_id', null],
                ['customer_email', 'guest@example.com'],
            ]);

        $itemMock->expects($this->once())
            ->method('getOrderID')
            ->willReturn(123);

        $itemMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);

        $itemMock->method('getData')
            ->willReturnMap([
                ['product_id', 456],
                ['qty_ordered', 5],
                ['qty_canceled', 1],
                ['qty_refunded', 1],
                ['price', 99.99],
                ['store_id', ''],
                ['created_at', '2026-07-06 10:00:00'],
            ]);

        $this->salesDataFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($salesDataMock);

        $salesDataMock->expects($this->once())->method('setOrderId')->with(123);
        $salesDataMock->expects($this->once())->method('setCustomerId')->with('guest@example.com');
        $salesDataMock->expects($this->once())->method('setProductId')->with(456);
        $salesDataMock->expects($this->once())->method('setQuantity')->with('3');
        $salesDataMock->expects($this->once())->method('setPrice')->with(99.99);
        $salesDataMock->expects($this->once())
            ->method('setCreatedAt')
            ->with($this->matchesRegularExpression('/^2026-07-06 10:00:00[+\-]\d{2}:\d{2}$/'));

        $collectionIterable = new class($collectionBaseMock, [$itemMock]) implements \IteratorAggregate {
            private $collectionBaseMock;
            private $items;

            public function __construct($collectionBaseMock, array $items)
            {
                $this->collectionBaseMock = $collectionBaseMock;
                $this->items = $items;
            }

            public function getSelect()
            {
                return $this->collectionBaseMock->getSelect();
            }

            public function addBindParam($key, $value)
            {
                return $this->collectionBaseMock->addBindParam($key, $value);
            }

            public function getIterator(): \Traversable
            {
                return new \ArrayIterator($this->items);
            }
        };

        $saleFactoryMock = $this->createMock(CollectionFactory::class);
        $saleFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionIterable);

        $saleHelper = new Sale(
            $this->storesConfigMock,
            $saleFactoryMock,
            $this->salesDataFactoryMock
        );

        $result = $saleHelper->getSales('All', 'All');

        $this->assertCount(1, $result);
        $this->assertSame($salesDataMock, $result[0]);
    }

    public function testGetSalesUsesStoreTimezoneWhenStoreIdExists(): void
    {
        $selectMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['where', 'limit'])
            ->getMock();

        $collectionBaseMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getSelect', 'addBindParam'])
            ->getMock();

        $collectionBaseMock->method('getSelect')->willReturn($selectMock);

        $orderMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getData'])
            ->getMock();

        $itemMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getOrderID', 'getOrder', 'getData'])
            ->getMock();

        $salesDataMock = $this->createMock(SalesDataInterface::class);

        $orderMock->method('getData')
            ->willReturnMap([
                ['customer_id', 99],
                ['customer_email', 'fallback@example.com'],
            ]);

        $itemMock->method('getOrderID')->willReturn(321);
        $itemMock->method('getOrder')->willReturn($orderMock);
        $itemMock->method('getData')
            ->willReturnMap([
                ['product_id', 654],
                ['qty_ordered', 2],
                ['qty_canceled', 0],
                ['qty_refunded', 0],
                ['price', 55.00],
                ['store_id', '0'],
                ['created_at', '2026-07-06 10:00:00'],
            ]);

        $this->storesConfigMock->expects($this->once())
            ->method('getStoresConfigByPath')
            ->willReturn([
                '0' => 'UTC',
            ]);

        $this->salesDataFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($salesDataMock);

        $salesDataMock->expects($this->once())->method('setCustomerId')->with(99);
        $salesDataMock->expects($this->once())->method('setCreatedAt')->with('2026-07-06 10:00:00+00:00');

        $collectionIterable = new class($collectionBaseMock, [$itemMock]) implements \IteratorAggregate {
            private $collectionBaseMock;
            private $items;

            public function __construct($collectionBaseMock, array $items)
            {
                $this->collectionBaseMock = $collectionBaseMock;
                $this->items = $items;
            }

            public function getSelect()
            {
                return $this->collectionBaseMock->getSelect();
            }

            public function addBindParam($key, $value)
            {
                return $this->collectionBaseMock->addBindParam($key, $value);
            }

            public function getIterator(): \Traversable
            {
                return new \ArrayIterator($this->items);
            }
        };

        $saleFactoryMock = $this->createMock(CollectionFactory::class);
        $saleFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionIterable);

        $saleHelper = new Sale(
            $this->storesConfigMock,
            $saleFactoryMock,
            $this->salesDataFactoryMock
        );

        $saleHelper->getSales('All', 'All');
        $this->addToAssertionCount(1);
    }
}
