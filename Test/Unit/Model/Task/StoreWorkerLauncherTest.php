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

namespace AthosCommerce\Feed\Test\Unit\Model\Task;

use Magento\Framework\Shell;
use Magento\Framework\ShellFactory;
use AthosCommerce\Feed\Api\StoreExecutionLockInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\ResourceModel\Task as TaskResource;
use AthosCommerce\Feed\Model\Task\StoreWorkerLauncher;

class StoreWorkerLauncherTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TaskResource
     */
    private $taskResourceMock;

    /**
     * @var ShellFactory
     */
    private $shellFactoryMock;

    /**
     * @var AthosCommerceLogger
     */
    private $loggerMock;
    /**
     * @var StoreExecutionLockInterface
     */
    private $storeExecutionLockMock;

    /**
     * @var StoreWorkerLauncher
     */
    private $launcher;

    protected function setUp(): void
    {
        $this->taskResourceMock = $this->createMock(TaskResource::class);
        $this->shellFactoryMock = $this->createMock(ShellFactory::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);
        $this->storeExecutionLockMock = $this->createMock(StoreExecutionLockInterface::class);
        $this->launcher = new StoreWorkerLauncher(
            $this->taskResourceMock,
            $this->shellFactoryMock,
            $this->loggerMock,
            $this->storeExecutionLockMock
        );
    }

    public function testSpawnPendingStoreWorkersSkipsLockedStores(): void
    {
        $shellMock = $this->createMock(Shell::class);

        $this->taskResourceMock->expects($this->once())
            ->method('getPendingStoreCodes')
            ->with(null)
            ->willReturn(['default', 'french']);
        $this->storeExecutionLockMock->expects($this->exactly(2))
            ->method('isLocked')
            ->willReturnMap([
                ['default', false],
                ['french', true],
            ]);
        $this->shellFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($shellMock);
        $shellMock->expects($this->once())
            ->method('execute')
            ->with(
                'cd %s && %s %s ' . StoreWorkerLauncher::WORKER_COMMAND_NAME . ' --store=%s > /dev/null 2>&1 &',
                [BP, PHP_BINARY, 'bin/magento', 'default']
            );

        $this->assertSame(['default'], $this->launcher->spawnPendingStoreWorkers());
    }

    public function testSpawnStoreWorkersUsesProvidedStoreCodes(): void
    {
        $shellMock = $this->createMock(Shell::class);

        $this->taskResourceMock->expects($this->never())
            ->method('getPendingStoreCodes');
        $this->storeExecutionLockMock->expects($this->exactly(2))
            ->method('isLocked')
            ->willReturnMap([
                ['default', false],
                ['french', true],
            ]);
        $this->shellFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($shellMock);
        $shellMock->expects($this->once())
            ->method('execute')
            ->with(
                'cd %s && %s %s ' . StoreWorkerLauncher::WORKER_COMMAND_NAME . ' --store=%s > /dev/null 2>&1 &',
                [BP, PHP_BINARY, 'bin/magento', 'default']
            );

        $this->assertSame(['default'], $this->launcher->spawnStoreWorkers(['default', 'french']));
    }

    public function testGetPendingStoreCodesPassesStoreFilterToResource(): void
    {
        $this->taskResourceMock->expects($this->once())
            ->method('getPendingStoreCodes')
            ->with('default')
            ->willReturn(['default']);

        $this->assertSame(['default'], $this->launcher->getPendingStoreCodes('default'));
    }
}
