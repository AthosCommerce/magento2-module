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

use AthosCommerce\Feed\Api\EntityDiscoveryInterface;
use AthosCommerce\Feed\Api\EntityDiscoveryInterfaceFactory;
use AthosCommerce\Feed\Api\LiveIndexingInterface;
use AthosCommerce\Feed\Api\LiveIndexingInterfaceFactory;
use AthosCommerce\Feed\Console\Command\EntityDiscoveryCommand;
use AthosCommerce\Feed\Console\Command\EntitySyncCommand;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\LiveIndexing\WorkerFailureLogger;
use AthosCommerce\Feed\Model\Metric\CollectorInterface;
use AthosCommerce\Feed\Model\Metric\Output\CliOutput;
use Magento\Framework\App\State;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * The live indexing commands run as detached cron workers (output to /dev/null), so a failure
 * must reach the module log, not only the console.
 *
 * @covers \AthosCommerce\Feed\Console\Command\EntitySyncCommand
 * @covers \AthosCommerce\Feed\Console\Command\EntityDiscoveryCommand
 * @covers \AthosCommerce\Feed\Model\LiveIndexing\WorkerFailureLogger
 */
class LiveIndexingCommandFailureTest extends TestCase
{
    /**
     * @var AthosCommerceLogger|MockObject
     */
    private $logger;
    /**
     * @var WorkerFailureLogger
     */
    private $workerFailureLogger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(AthosCommerceLogger::class);
        $this->workerFailureLogger = new WorkerFailureLogger($this->logger);
    }

    /**
     * A failing entity sync is logged with the command and store codes, and returns FAILURE.
     */
    public function testEntitySyncFailureIsLogged(): void
    {
        $liveIndexing = $this->createMock(LiveIndexingInterface::class);
        $liveIndexing->method('execute')->willThrowException(new \RuntimeException('endpoint unreachable'));
        $factory = $this->createMock(LiveIndexingInterfaceFactory::class);
        $factory->method('create')->willReturn($liveIndexing);

        $this->expectFailureLogged(EntitySyncCommand::COMMAND_NAME, 'endpoint unreachable');
        $command = new EntitySyncCommand(
            $factory,
            $this->createDateTimeFactory(),
            $this->createMock(State::class),
            $this->createMock(CliOutput::class),
            $this->createMock(CollectorInterface::class),
            $this->workerFailureLogger
        );

        $this->assertSame(Command::FAILURE, $this->runForDefaultStore($command));
    }

    /**
     * A failing discovery is logged with the command and store codes, and returns FAILURE.
     */
    public function testEntityDiscoveryFailureIsLogged(): void
    {
        $discovery = $this->createMock(EntityDiscoveryInterface::class);
        $discovery->method('execute')->willThrowException(new \RuntimeException('collection failed'));
        $factory = $this->createMock(EntityDiscoveryInterfaceFactory::class);
        $factory->method('create')->willReturn($discovery);

        $this->expectFailureLogged(EntityDiscoveryCommand::COMMAND_NAME, 'collection failed');
        $command = new EntityDiscoveryCommand(
            $factory,
            $this->createDateTimeFactory(),
            $this->createMock(State::class),
            $this->createMock(CliOutput::class),
            $this->createMock(CollectorInterface::class),
            $this->workerFailureLogger
        );

        $this->assertSame(Command::FAILURE, $this->runForDefaultStore($command));
    }

    /**
     * The shutdown handler logs nothing when no command is running (finished or never started).
     */
    public function testFatalHandlerIsSilentOutsideARun(): void
    {
        $this->logger->expects($this->never())->method('critical');

        $this->workerFailureLogger->logFatalError();
        $this->workerFailureLogger->start(EntitySyncCommand::COMMAND_NAME, ['default']);
        $this->workerFailureLogger->finish();
        $this->workerFailureLogger->logFatalError();
    }

    /**
     * @param string $commandName
     * @param string $message
     * @return void
     */
    private function expectFailureLogged(string $commandName, string $message): void
    {
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains($commandName . ' failed: ' . $message),
                $this->callback(static function (array $context) use ($commandName): bool {
                    return $context['command'] === $commandName
                        && $context['store_codes'] === ['default']
                        && $context['exception'] === \RuntimeException::class;
                })
            );
    }

    /**
     * Run the command for store "default".
     *
     * @param Command $command
     * @return int
     */
    private function runForDefaultStore(Command $command): int
    {
        return (new CommandTester($command))->execute(['--storecodes' => 'default']);
    }

    /**
     * @return DateTimeFactory|MockObject
     */
    private function createDateTimeFactory()
    {
        $dateTime = $this->createMock(DateTime::class);
        $dateTime->method('gmtDate')->willReturn('2026-10-01 00:00:00');
        $factory = $this->createMock(DateTimeFactory::class);
        $factory->method('create')->willReturn($dateTime);

        return $factory;
    }
}
