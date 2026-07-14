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

namespace AthosCommerce\Feed\Test\Unit\Model;

use Magento\Framework\Stdlib\DateTime\DateTime;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Api\Data\TaskErrorInterface;
use AthosCommerce\Feed\Api\Data\TaskErrorInterfaceFactory;
use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Api\MetadataInterface;
use AthosCommerce\Feed\Api\TaskRepositoryInterface;
use AthosCommerce\Feed\Exception\GenericException;
use AthosCommerce\Feed\Model\ExecuteTask;
use AthosCommerce\Feed\Model\Task\ExecutorInterface;
use AthosCommerce\Feed\Model\Task\ExecutorPool;

class ExecuteTaskTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ExecutorPool
     */
    private $executorPoolMock;

    /**
     * @var TaskRepositoryInterface
     */
    private $taskRepositoryMock;

    /**
     * @var DateTime
     */
    private $dateTimeMock;

    /**
     * @var TaskErrorInterfaceFactory
     */
    private $taskErrorFactoryMock;

    /**
     * @var AthosCommerceLogger
     */
    private $loggerMock;

    private $executeTask;

    public function setUp(): void
    {
        $this->executorPoolMock = $this->createMock(ExecutorPool::class);
        $this->taskRepositoryMock = $this->createMock(TaskRepositoryInterface::class);
        $this->dateTimeMock = $this->createMock(DateTime::class);
        $this->taskErrorFactoryMock = $this->createMock(TaskErrorInterfaceFactory::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);
        $this->executeTask = new ExecuteTask(
            $this->executorPoolMock,
            $this->taskRepositoryMock,
            $this->dateTimeMock,
            $this->taskErrorFactoryMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $type = 'type';
        $time = '10-10-1990 12:40';
        $statusCalls = [];

        $taskMock = $this->createMock(TaskInterface::class);
        $executorMock = $this->createMock(ExecutorInterface::class);

        $taskMock->expects($this->once())
            ->method('getType')
            ->willReturn($type);

        $this->executorPoolMock->expects($this->once())
            ->method('get')
            ->with($type)
            ->willReturn($executorMock);

        $this->dateTimeMock->expects($this->exactly(2))
            ->method('gmtDate')
            ->willReturn($time);

        $taskMock->expects($this->once())
            ->method('setStartedAt')
            ->with($time)
            ->willReturnSelf();

        $taskMock->expects($this->exactly(2))
            ->method('setStatus')
            ->willReturnCallback(function ($status) use (&$statusCalls, $taskMock) {
                $statusCalls[] = $status;
                return $taskMock;
            });

        $this->taskRepositoryMock->expects($this->exactly(2))
            ->method('save')
            ->with($taskMock)
            ->willReturn($taskMock);

        $executorMock->expects($this->once())
            ->method('execute')
            ->with($taskMock);

        $taskMock->expects($this->once())
            ->method('setEndedAt')
            ->with($time)
            ->willReturnSelf();

        $this->assertSame(
            MetadataInterface::TASK_STATUS_SUCCESS,
            $this->executeTask->execute($taskMock)
        );

        $this->assertSame(
            [
                MetadataInterface::TASK_STATUS_PROCESSING,
                MetadataInterface::TASK_STATUS_SUCCESS,
            ],
            $statusCalls
        );
    }

    public function testExecuteExceptionCase()
    {
        $type = 'type';
        $time = '10-10-1990 12:40';
        $statusCalls = [];

        $taskErrorMock = $this->createMock(TaskErrorInterface::class);
        $taskMock = $this->createMock(TaskInterface::class);
        $executorMock = $this->createMock(ExecutorInterface::class);

        $taskMock->expects($this->once())
            ->method('getType')
            ->willReturn($type);

        $this->executorPoolMock->expects($this->once())
            ->method('get')
            ->with($type)
            ->willReturn($executorMock);

        $this->dateTimeMock->expects($this->exactly(2))
            ->method('gmtDate')
            ->willReturn($time);

        $taskMock->expects($this->once())
            ->method('setStartedAt')
            ->with($time)
            ->willReturnSelf();

        $taskMock->expects($this->exactly(2))
            ->method('setStatus')
            ->willReturnCallback(function ($status) use (&$statusCalls, $taskMock) {
                $statusCalls[] = $status;
                return $taskMock;
            });

        $this->taskRepositoryMock->expects($this->exactly(2))
            ->method('save')
            ->with($taskMock)
            ->willReturn($taskMock);

        $executorMock->expects($this->once())
            ->method('execute')
            ->with($taskMock)
            ->willThrowException(new \Exception('exception message'));

        $this->taskErrorFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($taskErrorMock);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->withAnyParameters();

        $taskErrorMock->expects($this->once())
            ->method('setMessage')
            ->with('exception message')
            ->willReturnSelf();

        $taskErrorMock->expects($this->once())
            ->method('setCode')
            ->with(GenericException::CODE)
            ->willReturnSelf();

        $taskMock->expects($this->once())
            ->method('setError')
            ->with($taskErrorMock)
            ->willReturnSelf();

        $taskMock->expects($this->once())
            ->method('setEndedAt')
            ->with($time)
            ->willReturnSelf();

        $this->assertSame(
            MetadataInterface::TASK_STATUS_ERROR,
            $this->executeTask->execute($taskMock)
        );

        $this->assertSame(
            [
                MetadataInterface::TASK_STATUS_PROCESSING,
                MetadataInterface::TASK_STATUS_ERROR,
            ],
            $statusCalls
        );
    }

    public function testExecuteReturnsSuccessAndDoesNotCreateError()
    {
        $type = 'type';
        $time = '10-10-1990 12:40';
        $statusCalls = [];

        $taskMock = $this->createMock(TaskInterface::class);
        $executorMock = $this->createMock(ExecutorInterface::class);

        $taskMock->expects($this->once())
            ->method('getType')
            ->willReturn($type);

        $this->executorPoolMock->expects($this->once())
            ->method('get')
            ->with($type)
            ->willReturn($executorMock);

        $this->dateTimeMock->expects($this->exactly(2))
            ->method('gmtDate')
            ->willReturn($time);

        $taskMock->expects($this->once())
            ->method('setStartedAt')
            ->with($time)
            ->willReturnSelf();

        $taskMock->expects($this->exactly(2))
            ->method('setStatus')
            ->willReturnCallback(function ($status) use (&$statusCalls, $taskMock) {
                $statusCalls[] = $status;
                return $taskMock;
            });

        $this->taskRepositoryMock->expects($this->exactly(2))
            ->method('save')
            ->with($taskMock)
            ->willReturn($taskMock);

        $executorMock->expects($this->once())
            ->method('execute')
            ->with($taskMock);

        $this->taskErrorFactoryMock->expects($this->never())
            ->method('create');

        $taskMock->expects($this->never())
            ->method('setError');

        $taskMock->expects($this->once())
            ->method('setEndedAt')
            ->with($time)
            ->willReturnSelf();

        $this->assertSame(
            MetadataInterface::TASK_STATUS_SUCCESS,
            $this->executeTask->execute($taskMock)
        );

        $this->assertSame(
            [
                MetadataInterface::TASK_STATUS_PROCESSING,
                MetadataInterface::TASK_STATUS_SUCCESS,
            ],
            $statusCalls
        );
    }

    public function testExecuteUsesFallbackErrorCodeForNonGenericException()
    {
        $type = 'type';
        $time = '10-10-1990 12:40';

        $taskErrorMock = $this->createMock(TaskErrorInterface::class);
        $taskMock = $this->createMock(TaskInterface::class);
        $executorMock = $this->createMock(ExecutorInterface::class);

        $taskMock->expects($this->once())
            ->method('getType')
            ->willReturn($type);

        $this->executorPoolMock->expects($this->once())
            ->method('get')
            ->with($type)
            ->willReturn($executorMock);

        $this->dateTimeMock->expects($this->exactly(2))
            ->method('gmtDate')
            ->willReturn($time);

        $taskMock->expects($this->once())
            ->method('setStartedAt')
            ->with($time)
            ->willReturnSelf();

        $taskMock->expects($this->exactly(2))
            ->method('setStatus')
            ->willReturnCallback(function ($status) use (&$statusCalls, $taskMock) {
                $statusCalls[] = $status;
                return $taskMock;
            });

        $this->taskRepositoryMock->expects($this->exactly(2))
            ->method('save')
            ->with($taskMock)
            ->willReturn($taskMock);

        $executorMock->expects($this->once())
            ->method('execute')
            ->with($taskMock)
            ->willThrowException(new \Exception('exception message'));

        $this->taskErrorFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($taskErrorMock);

        $taskErrorMock->expects($this->once())
            ->method('setMessage')
            ->with('exception message')
            ->willReturnSelf();

        $taskErrorMock->expects($this->once())
            ->method('setCode')
            ->with(GenericException::CODE)
            ->willReturnSelf();

        $taskMock->expects($this->once())
            ->method('setError')
            ->with($taskErrorMock)
            ->willReturnSelf();

        $taskMock->expects($this->once())
            ->method('setEndedAt')
            ->with($time)
            ->willReturnSelf();

        $this->assertSame(
            MetadataInterface::TASK_STATUS_ERROR,
            $this->executeTask->execute($taskMock)
        );
    }

    public function testExecuteUsesGenericExceptionCode()
    {
        $type = 'type';
        $time = '10-10-1990 12:40';
        $exceptionCode = 12345;

        $taskErrorMock = $this->createMock(TaskErrorInterface::class);
        $taskMock = $this->createMock(TaskInterface::class);
        $executorMock = $this->createMock(ExecutorInterface::class);

        $taskMock->expects($this->once())
            ->method('getType')
            ->willReturn($type);

        $this->executorPoolMock->expects($this->once())
            ->method('get')
            ->with($type)
            ->willReturn($executorMock);

        $this->dateTimeMock->expects($this->exactly(2))
            ->method('gmtDate')
            ->willReturn($time);

        $taskMock->expects($this->once())
            ->method('setStartedAt')
            ->with($time)
            ->willReturnSelf();

        $statusCalls = [];
        $taskMock->expects($this->exactly(2))
            ->method('setStatus')
            ->willReturnCallback(function ($status) use (&$statusCalls, $taskMock) {
                $statusCalls[] = $status;
                return $taskMock;
            });

        $this->taskRepositoryMock->expects($this->exactly(2))
            ->method('save')
            ->with($taskMock)
            ->willReturn($taskMock);

        $executorMock->expects($this->once())
            ->method('execute')
            ->with($taskMock)
            ->willThrowException(new GenericException('generic exception message', $exceptionCode));

        $this->taskErrorFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($taskErrorMock);

        $taskErrorMock->expects($this->once())
            ->method('setMessage')
            ->with('generic exception message')
            ->willReturnSelf();

        $taskErrorMock->expects($this->once())
            ->method('setCode')
            ->with($exceptionCode)
            ->willReturnSelf();

        $taskMock->expects($this->once())
            ->method('setError')
            ->with($taskErrorMock)
            ->willReturnSelf();

        $taskMock->expects($this->once())
            ->method('setEndedAt')
            ->with($time)
            ->willReturnSelf();

        $this->assertSame(
            MetadataInterface::TASK_STATUS_ERROR,
            $this->executeTask->execute($taskMock)
        );
    }

    public function testExecuteDoesNotCatchExecutorPoolException()
    {
        $type = 'unknown_type';

        $taskMock = $this->createMock(TaskInterface::class);

        $taskMock->expects($this->once())
            ->method('getType')
            ->willReturn($type);

        $this->executorPoolMock->expects($this->once())
            ->method('get')
            ->with($type)
            ->willThrowException(new \Exception('No task executor for type unknown_type'));

        $this->dateTimeMock->expects($this->never())
            ->method('gmtDate');

        $this->taskRepositoryMock->expects($this->never())
            ->method('save');

        $this->taskErrorFactoryMock->expects($this->never())
            ->method('create');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No task executor for type unknown_type');

        $this->executeTask->execute($taskMock);
    }
}
