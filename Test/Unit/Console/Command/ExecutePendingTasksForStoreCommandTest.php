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
use AthosCommerce\Feed\Console\Command\ExecutePendingTasksForStoreCommand;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Metric\CollectorInterface;
use AthosCommerce\Feed\Model\Metric\Output\CliOutput;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ExecutePendingTasksForStoreCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testExecuteRequiresStoreOption(): void
    {
        $command = $this->createCommand();
        $tester = new CommandTester($command);

        $this->assertSame(2, $tester->execute([]));
    }

    public function testExecuteRunsStoreWorker(): void
    {
        $executePendingTasksMock = $this->createMock(ExecutePendingTasksInterface::class);
        $factoryMock = $this->createMock(ExecutePendingTasksInterfaceFactory::class);
        $command = $this->createCommand($factoryMock);
        $tester = new CommandTester($command);

        $factoryMock->expects($this->once())
            ->method('create')
            ->willReturn($executePendingTasksMock);
        $executePendingTasksMock->expects($this->once())
            ->method('executeForStoreWorker')
            ->with('default', 'cli')
            ->willReturn([11 => 'success']);

        $this->assertSame(0, $tester->execute(['--store' => 'default']));
        $this->assertStringContainsString('Task ID 11: success', $tester->getDisplay());
    }

    /**
     * @param ExecutePendingTasksInterfaceFactory|null $factory
     * @return ExecutePendingTasksForStoreCommand
     */
    private function createCommand(
        ?ExecutePendingTasksInterfaceFactory $factory = null
    ): ExecutePendingTasksForStoreCommand
    {
        $factory = $factory ?? $this->createMock(ExecutePendingTasksInterfaceFactory::class);
        $dateTimeFactoryMock = $this->createMock(DateTimeFactory::class);
        $dateTimeMock = $this->createMock(DateTime::class);
        $stateMock = $this->createMock(State::class);
        $cliOutputMock = $this->createMock(CliOutput::class);
        $metricCollectorMock = $this->createMock(CollectorInterface::class);
        $loggerMock = $this->createMock(AthosCommerceLogger::class);

        $dateTimeFactoryMock->method('create')->willReturn($dateTimeMock);
        $dateTimeMock->method('gmtDate')->willReturn('2026-09-06 00:00:00');

        $command = new ExecutePendingTasksForStoreCommand(
            $factory,
            $dateTimeFactoryMock,
            $stateMock,
            $cliOutputMock,
            $metricCollectorMock,
            $loggerMock
        );
        $application = new Application();
        $application->add($command);

        return $application->find(ExecutePendingTasksForStoreCommand::COMMAND_NAME);
    }
}
