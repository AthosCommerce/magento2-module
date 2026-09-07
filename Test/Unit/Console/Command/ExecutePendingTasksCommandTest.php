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

namespace AthosCommerce\Feed\Test\Unit\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use AthosCommerce\Feed\Api\ExecutePendingTasksInterface;
use AthosCommerce\Feed\Api\ExecutePendingTasksInterfaceFactory;
use AthosCommerce\Feed\Console\Command\ExecutePendingTasksCommand;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Metric\CollectorInterface;
use AthosCommerce\Feed\Model\Metric\Output\CliOutput;
use AthosCommerce\Feed\Model\Task\StoreWorkerLauncher;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ExecutePendingTasksCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testExecuteRunsSequentiallyByDefault(): void
    {
        $executePendingTasksMock = $this->createMock(ExecutePendingTasksInterface::class);
        $factoryMock = $this->createMock(ExecutePendingTasksInterfaceFactory::class);
        $command = $this->createCommand($factoryMock);
        $tester = new CommandTester($command);

        $factoryMock->expects($this->once())
            ->method('create')
            ->willReturn($executePendingTasksMock);
        $executePendingTasksMock->expects($this->once())
            ->method('execute')
            ->with(null, 'cli')
            ->willReturn([12 => 'success']);

        $this->assertSame(0, $tester->execute([]));
        $this->assertStringContainsString('Task ID 12: success', $tester->getDisplay());
    }

    public function testExecuteSpawnsParallelWorkersWhenRequested(): void
    {
        $factoryMock = $this->createMock(ExecutePendingTasksInterfaceFactory::class);
        $launcherMock = $this->createMock(StoreWorkerLauncher::class);
        $command = $this->createCommand($factoryMock, $launcherMock);
        $tester = new CommandTester($command);

        $factoryMock->expects($this->never())->method('create');
        $launcherMock->expects($this->once())
            ->method('spawnPendingStoreWorkers')
            ->with(null)
            ->willReturn(['default', 'french']);

        $this->assertSame(0, $tester->execute(['--parallel' => true]));
        $this->assertStringContainsString('Spawned worker for store "default".', $tester->getDisplay());
        $this->assertStringContainsString('Spawned worker for store "french".', $tester->getDisplay());
    }

    /**
     * @param ExecutePendingTasksInterfaceFactory|null $factory
     * @param StoreWorkerLauncher|null $launcher
     * @return ExecutePendingTasksCommand
     */
    private function createCommand(
        ?ExecutePendingTasksInterfaceFactory $factory = null,
        ?StoreWorkerLauncher $launcher = null
    ): ExecutePendingTasksCommand {
        $factory = $factory ?? $this->createMock(ExecutePendingTasksInterfaceFactory::class);
        $launcher = $launcher ?? $this->createMock(StoreWorkerLauncher::class);
        $dateTimeFactoryMock = $this->createMock(DateTimeFactory::class);
        $dateTimeMock = $this->createMock(DateTime::class);
        $stateMock = $this->createMock(State::class);
        $cliOutputMock = $this->createMock(CliOutput::class);
        $metricCollectorMock = $this->createMock(CollectorInterface::class);
        $loggerMock = $this->createMock(AthosCommerceLogger::class);

        $dateTimeFactoryMock->method('create')->willReturn($dateTimeMock);
        $dateTimeMock->method('gmtDate')->willReturn('2026-09-06 00:00:00');

        $command = new ExecutePendingTasksCommand(
            $factory,
            $dateTimeFactoryMock,
            $stateMock,
            $cliOutputMock,
            $metricCollectorMock,
            $loggerMock,
            $launcher
        );
        $application = new Application();
        $application->add($command);

        return $application->find(ExecutePendingTasksCommand::COMMAND_NAME);
    }
}
