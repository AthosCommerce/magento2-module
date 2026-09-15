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

namespace AthosCommerce\Feed\Test\Unit\Cron;

use AthosCommerce\Feed\Api\ExecutePendingTasksInterface;
use AthosCommerce\Feed\Cron\ExecuteTasksCron;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Metric\CollectorInterface;
use AthosCommerce\Feed\Model\Metric\Output\LogOutput;
use AthosCommerce\Feed\Model\Task\StoreWorkerLauncher;
use AthosCommerce\Feed\Service\Config;

class ExecuteTasksTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ExecutePendingTasksInterface
     */
    private $executePendingTaskInterfaceMock;

    private $executeTasks;

    private $loggerMock;

    private $configMock;

    private $storeWorkerLauncherMock;

    private $logOutputMock;

    private $metricCollectorMock;

    public function setUp(): void
    {
        $this->executePendingTaskInterfaceMock = $this->createMock(ExecutePendingTasksInterface::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);
        $this->configMock = $this->createMock(Config::class);
        $this->storeWorkerLauncherMock = $this->createMock(StoreWorkerLauncher::class);
        $this->logOutputMock = $this->createMock(LogOutput::class);
        $this->metricCollectorMock = $this->createMock(CollectorInterface::class);
        $this->executeTasks = new ExecuteTasksCron(
            $this->executePendingTaskInterfaceMock,
            $this->loggerMock,
            $this->configMock,
            $this->storeWorkerLauncherMock,
            $this->logOutputMock,
            $this->metricCollectorMock
        );
    }

    public function testExecuteRunsSequentiallyWhenParallelCronIsDisabled()
    {
        $this->configMock->expects($this->once())
            ->method('isParallelCronEnabled')
            ->willReturn(false);
        $this->executePendingTaskInterfaceMock->expects($this->once())
            ->method('execute')
            ->with(null, ExecutePendingTasksInterface::EXECUTION_MODE_CRON)
            ->willReturn([]);
        $this->storeWorkerLauncherMock->expects($this->never())
            ->method('spawnPendingStoreWorkers');
        $this->metricCollectorMock->expects($this->once())
            ->method('setOutput')
            ->with($this->logOutputMock);
        $this->metricCollectorMock->expects($this->exactly(2))
            ->method('reset')
            ->with(CollectorInterface::CODE_TASK_EXECUTION);
        $sequentialCollectCalls = 0;
        $this->metricCollectorMock->expects($this->exactly(2))
            ->method('collect')
            ->willReturnCallback(
                function (string $code, ?string $title, array $data) use (&$sequentialCollectCalls): void {
                    $sequentialCollectCalls++;
                    if ($sequentialCollectCalls === 1) {
                        $this->assertSame(CollectorInterface::CODE_TASK_EXECUTION, $code);
                        $this->assertSame('Cron Start', $title);
                        $this->assertSame([
                            'execution_mode' => ExecutePendingTasksInterface::EXECUTION_MODE_CRON,
                            'parallel_enabled' => '0',
                        ], $data);
                        return;
                    }

                    $this->assertSame(CollectorInterface::CODE_TASK_EXECUTION, $code);
                    $this->assertSame('Cron Sequential Execution', $title);
                    $this->assertSame([
                        'execution_mode' => ExecutePendingTasksInterface::EXECUTION_MODE_CRON,
                        'parallel_enabled' => '0',
                        'processed_task_count' => 0,
                    ], $data);
                }
            );
        $this->metricCollectorMock->expects($this->once())
            ->method('print')
            ->with(
                CollectorInterface::CODE_TASK_EXECUTION,
                CollectorInterface::PRINT_TYPE_FULL
            );

        $this->assertSame(null, $this->executeTasks->execute());
    }

    public function testExecuteSpawnsWorkersWhenParallelCronIsEnabled(): void
    {
        $this->configMock->expects($this->once())
            ->method('isParallelCronEnabled')
            ->willReturn(true);
        $this->executePendingTaskInterfaceMock->expects($this->never())
            ->method('execute');
        $this->metricCollectorMock->expects($this->once())
            ->method('setOutput')
            ->with($this->logOutputMock);
        $this->metricCollectorMock->expects($this->exactly(2))
            ->method('reset')
            ->with(CollectorInterface::CODE_TASK_EXECUTION);
        $this->storeWorkerLauncherMock->expects($this->once())
            ->method('getPendingStoreCodes')
            ->with(null)
            ->willReturn(['default', 'french']);
        $this->storeWorkerLauncherMock->expects($this->once())
            ->method('spawnStoreWorkers')
            ->with(['default', 'french'])
            ->willReturn(['default']);
        $parallelCollectCalls = 0;
        $this->metricCollectorMock->expects($this->exactly(2))
            ->method('collect')
            ->willReturnCallback(
                function (string $code, ?string $title, array $data) use (&$parallelCollectCalls): void {
                    $parallelCollectCalls++;
                    if ($parallelCollectCalls === 1) {
                        $this->assertSame(CollectorInterface::CODE_TASK_EXECUTION, $code);
                        $this->assertSame('Cron Start', $title);
                        $this->assertSame([
                            'execution_mode' => ExecutePendingTasksInterface::EXECUTION_MODE_CRON,
                            'parallel_enabled' => '1',
                        ], $data);
                        return;
                    }

                    $this->assertSame(CollectorInterface::CODE_TASK_EXECUTION, $code);
                    $this->assertSame('Cron Parallel Dispatch', $title);
                    $this->assertSame([
                        'execution_mode' => ExecutePendingTasksInterface::EXECUTION_MODE_CRON,
                        'parallel_enabled' => '1',
                        'pending_store_count' => 2,
                        'spawned_store_count' => 1,
                    ], $data);
                }
            );
        $this->metricCollectorMock->expects($this->once())
            ->method('print')
            ->with(
                CollectorInterface::CODE_TASK_EXECUTION,
                CollectorInterface::PRINT_TYPE_FULL
            );

        $this->assertSame(null, $this->executeTasks->execute());
    }
}
