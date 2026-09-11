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

namespace AthosCommerce\Feed\Test\Unit\Model\Api;

require_once dirname(__DIR__, 2) . '/_files/bootstrap-stubs.php';

use AthosCommerce\Feed\Api\Data\TaskErrorItemInterface;
use AthosCommerce\Feed\Api\Data\TaskErrorItemInterfaceFactory;
use AthosCommerce\Feed\Api\Data\TaskErrorListResponseInterface;
use AthosCommerce\Feed\Api\Data\TaskErrorListResponseInterfaceFactory;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Api\GetTaskErrors;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\TestCase;

class GetTaskErrorsTest extends TestCase
{
    private $resourceConnectionMock;
    private $itemFactoryMock;
    private $responseFactoryMock;
    private $loggerMock;
    private $model;

    protected function setUp(): void
    {
        $this->resourceConnectionMock = $this->createMock(ResourceConnection::class);
        $this->itemFactoryMock = $this->createMock(TaskErrorItemInterfaceFactory::class);
        $this->responseFactoryMock = $this->createMock(TaskErrorListResponseInterfaceFactory::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->model = new GetTaskErrors(
            $this->resourceConnectionMock,
            $this->itemFactoryMock,
            $this->responseFactoryMock,
            $this->loggerMock
        );
    }

    public function testGetListReturnsPaginatedItems(): void
    {
        $connectionMock = $this->getMockForAbstractClass(AdapterInterface::class);
        $countSelectMock = $this->getMockBuilder(Select::class)->disableOriginalConstructor()->getMock();
        $dataSelectMock = $this->getMockBuilder(Select::class)->disableOriginalConstructor()->getMock();
        $responseMock = $this->createMock(TaskErrorListResponseInterface::class);
        $itemOneMock = $this->createMock(TaskErrorItemInterface::class);
        $itemTwoMock = $this->createMock(TaskErrorItemInterface::class);
        $orders = [];

        $this->resourceConnectionMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($connectionMock);
        $this->resourceConnectionMock->expects($this->once())
            ->method('getTableName')
            ->with('athoscommerce_task_error')
            ->willReturn('athoscommerce_task_error');

        $connectionMock->expects($this->exactly(2))
            ->method('select')
            ->willReturnOnConsecutiveCalls($countSelectMock, $dataSelectMock);

        $countSelectMock->expects($this->once())
            ->method('from')
            ->with('athoscommerce_task_error', ['COUNT(*)'])
            ->willReturnSelf();

        $dataSelectMock->expects($this->once())
            ->method('from')
            ->with('athoscommerce_task_error', ['task_id', 'code', 'message', 'created_at'])
            ->willReturnSelf();
        $dataSelectMock->expects($this->exactly(2))
            ->method('order')
            ->willReturnCallback(function (string $order) use (&$orders, $dataSelectMock) {
                $orders[] = $order;
                return $dataSelectMock;
            });
        $dataSelectMock->expects($this->once())
            ->method('limitPage')
            ->with(2, 2)
            ->willReturnSelf();

        $connectionMock->expects($this->once())
            ->method('fetchOne')
            ->with($countSelectMock)
            ->willReturn('3');
        $connectionMock->expects($this->once())
            ->method('fetchAll')
            ->with($dataSelectMock)
            ->willReturn([
                ['task_id' => 9, 'code' => 500, 'message' => 'First error', 'created_at' => '2026-09-10 10:00:00'],
                ['task_id' => 8, 'code' => 400, 'message' => 'Second error', 'created_at' => '2026-09-10 09:00:00'],
            ]);

        $this->itemFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($itemOneMock, $itemTwoMock);

        $itemOneMock->expects($this->once())->method('setTaskId')->with(9)->willReturnSelf();
        $itemOneMock->expects($this->once())->method('setCode')->with(500)->willReturnSelf();
        $itemOneMock->expects($this->once())->method('setMessage')->with('First error')->willReturnSelf();
        $itemOneMock->expects($this->once())->method('setCreatedAt')->with('2026-09-10 10:00:00')->willReturnSelf();

        $itemTwoMock->expects($this->once())->method('setTaskId')->with(8)->willReturnSelf();
        $itemTwoMock->expects($this->once())->method('setCode')->with(400)->willReturnSelf();
        $itemTwoMock->expects($this->once())->method('setMessage')->with('Second error')->willReturnSelf();
        $itemTwoMock->expects($this->once())->method('setCreatedAt')->with('2026-09-10 09:00:00')->willReturnSelf();

        $this->responseFactoryMock->expects($this->once())->method('create')->willReturn($responseMock);
        $responseMock->expects($this->once())->method('setItems')->with([$itemOneMock, $itemTwoMock])->willReturnSelf();
        $responseMock->expects($this->once())->method('setTotal')->with(3)->willReturnSelf();
        $responseMock->expects($this->once())->method('setCurrentPage')->with(2)->willReturnSelf();
        $responseMock->expects($this->once())->method('setPageSize')->with(2)->willReturnSelf();
        $this->loggerMock->expects($this->once())->method('info');

        $result = $this->model->getList(2, 2);

        $this->assertSame(['created_at DESC', 'task_id DESC'], $orders);
        $this->assertSame($responseMock, $result);
    }

    public function testGetListAppliesTaskIdFilterAndNormalizesPaging(): void
    {
        $connectionMock = $this->getMockForAbstractClass(AdapterInterface::class);
        $countSelectMock = $this->getMockBuilder(Select::class)->disableOriginalConstructor()->getMock();
        $dataSelectMock = $this->getMockBuilder(Select::class)->disableOriginalConstructor()->getMock();
        $responseMock = $this->createMock(TaskErrorListResponseInterface::class);

        $this->resourceConnectionMock->method('getConnection')->willReturn($connectionMock);
        $this->resourceConnectionMock->method('getTableName')->willReturn('athoscommerce_task_error');
        $connectionMock->method('select')->willReturnOnConsecutiveCalls($countSelectMock, $dataSelectMock);

        $countSelectMock->method('from')->willReturnSelf();
        $dataSelectMock->method('from')->willReturnSelf();
        $dataSelectMock->method('order')->willReturnSelf();
        $dataSelectMock->expects($this->once())->method('limitPage')->with(1, 1)->willReturnSelf();

        $countSelectMock->expects($this->once())->method('where')->with('task_id = ?', 12)->willReturnSelf();
        $dataSelectMock->expects($this->once())->method('where')->with('task_id = ?', 12)->willReturnSelf();

        $connectionMock->method('fetchOne')->willReturn('0');
        $connectionMock->method('fetchAll')->willReturn([]);

        $this->responseFactoryMock->expects($this->once())->method('create')->willReturn($responseMock);
        $responseMock->expects($this->once())->method('setItems')->with([])->willReturnSelf();
        $responseMock->expects($this->once())->method('setTotal')->with(0)->willReturnSelf();
        $responseMock->expects($this->once())->method('setCurrentPage')->with(1)->willReturnSelf();
        $responseMock->expects($this->once())->method('setPageSize')->with(1)->willReturnSelf();
        $this->loggerMock->expects($this->once())->method('info');

        $result = $this->model->getList(0, 0, 12);

        $this->assertSame($responseMock, $result);
    }
}
