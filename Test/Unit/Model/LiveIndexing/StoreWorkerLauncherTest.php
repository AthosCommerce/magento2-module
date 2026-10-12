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

namespace AthosCommerce\Feed\Test\Unit\Model\LiveIndexing;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\LiveIndexing\StoreLock;
use AthosCommerce\Feed\Model\LiveIndexing\StoreWorkerLauncher;
use AthosCommerce\Feed\Service\Provider\LiveIndexingSiteProvider;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\OsInfo;
use Magento\Framework\Process\PhpExecutableFinderFactory;
use Magento\Framework\Shell\CommandRendererBackground;
use Magento\Framework\ShellInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AthosCommerce\Feed\Model\LiveIndexing\StoreWorkerLauncher
 * @covers \AthosCommerce\Feed\Model\LiveIndexing\StoreLock
 */
class StoreWorkerLauncherTest extends TestCase
{
    private const COMMAND = 'athoscommerce:indexing:entity-discovery';
    private const PHP = '/usr/bin/php-cli-test';

    /**
     * @var LiveIndexingSiteProvider|MockObject
     */
    private $siteProvider;
    /**
     * @var StoreLock|MockObject
     */
    private $storeLock;
    /**
     * @var ShellInterface|MockObject
     */
    private $shell;
    /**
     * @var StoreWorkerLauncher
     */
    private $launcher;

    protected function setUp(): void
    {
        $this->siteProvider = $this->createMock(LiveIndexingSiteProvider::class);
        $this->storeLock = $this->createMock(StoreLock::class);
        $this->shell = $this->createMock(ShellInterface::class);
        $finder = $this->createMock(PhpExecutableFinder::class);
        $finder->method('find')->willReturn(self::PHP);
        $finderFactory = $this->createMock(PhpExecutableFinderFactory::class);
        $finderFactory->method('create')->willReturn($finder);

        $this->launcher = new StoreWorkerLauncher(
            $this->siteProvider,
            $this->storeLock,
            $this->shell,
            $finderFactory,
            $this->createMock(AthosCommerceLogger::class)
        );
    }

    /**
     * Every eligible store gets its own background worker, started in the same run.
     */
    public function testSpawnStartsOneBackgroundWorkerPerStore(): void
    {
        $this->siteProvider->method('getLiveIndexingStores')
            ->willReturn([$this->store('default'), $this->store('second')]);
        $this->storeLock->method('isLocked')->willReturn(false);

        $calls = [];
        $this->shell->expects($this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function (string $command, array $arguments) use (&$calls) {
                $calls[] = [$command, $arguments];
                return '';
            });

        $this->assertSame(['default', 'second'], $this->launcher->spawn(self::COMMAND, StoreLock::TYPE_DISCOVERY));
        foreach ([0 => 'default', 1 => 'second'] as $index => $storeCode) {
            [$command, $arguments] = $calls[$index];
            $this->assertSame('%s %s %s --storecodes=%s', $command);
            $this->assertSame([self::PHP, BP . '/bin/magento', self::COMMAND, $storeCode], $arguments);
        }
    }

    /**
     * With the background renderer configured in di.xml, the worker command is escaped and
     * detached exactly like Magento cron's own group processes.
     */
    public function testBackgroundRendererEscapesAndDetachesWorker(): void
    {
        $osInfo = $this->createMock(OsInfo::class);
        $osInfo->method('isWindows')->willReturn(false);
        $rendered = (new CommandRendererBackground($osInfo))->render(
            '%s %s %s --storecodes=%s',
            [self::PHP, BP . '/bin/magento', self::COMMAND, 'default']
        );

        $this->assertSame(
            sprintf(
                "'%s' '%s' '%s' --storecodes='default' 2>/dev/null >/dev/null &",
                self::PHP,
                BP . '/bin/magento',
                self::COMMAND
            ),
            $rendered
        );
    }

    /**
     * A store whose previous run still holds the lock is not started again; the others are.
     */
    public function testSpawnSkipsStoreStillRunning(): void
    {
        $this->siteProvider->method('getLiveIndexingStores')
            ->willReturn([$this->store('default'), $this->store('second')]);
        $this->storeLock->method('isLocked')
            ->willReturnCallback(static fn (string $type, string $storeCode): bool => $storeCode === 'default');
        $this->shell->expects($this->once())->method('execute');

        $this->assertSame(['second'], $this->launcher->spawn(self::COMMAND, StoreLock::TYPE_SYNC));
    }

    /**
     * A store code that is not safe on a command line is never passed to the shell.
     */
    public function testSpawnRejectsUnsafeStoreCode(): void
    {
        $this->siteProvider->method('getLiveIndexingStores')->willReturn([$this->store('bad;code')]);
        $this->shell->expects($this->never())->method('execute');

        $this->assertSame([], $this->launcher->spawn(self::COMMAND, StoreLock::TYPE_DISCOVERY));
    }

    /**
     * A failed spawn for one store does not stop the others.
     */
    public function testSpawnContinuesAfterFailure(): void
    {
        $this->siteProvider->method('getLiveIndexingStores')
            ->willReturn([$this->store('default'), $this->store('second')]);
        $this->storeLock->method('isLocked')->willReturn(false);
        $this->shell->method('execute')->willReturnCallback(
            static function (string $command, array $arguments) {
                if (end($arguments) === 'default') {
                    throw new \RuntimeException('spawn failed');
                }
                return '';
            }
        );

        $this->assertSame(['second'], $this->launcher->spawn(self::COMMAND, StoreLock::TYPE_SYNC));
    }

    /**
     * Discovery and sync use different locks per store, so they never block each other.
     */
    public function testLockNamesDifferPerJobTypeAndStore(): void
    {
        $names = [];
        $lockManager = $this->createMock(LockManagerInterface::class);
        $lockManager->method('lock')->willReturnCallback(function (string $name) use (&$names) {
            $names[] = $name;
            return true;
        });
        $storeLock = new StoreLock($lockManager);

        $storeLock->acquire(StoreLock::TYPE_DISCOVERY, 'default');
        $storeLock->acquire(StoreLock::TYPE_SYNC, 'default');
        $storeLock->acquire(StoreLock::TYPE_DISCOVERY, 'second');

        $this->assertCount(3, array_unique($names));
    }

    /**
     * @param string $code
     * @return StoreInterface|MockObject
     */
    private function store(string $code)
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getCode')->willReturn($code);

        return $store;
    }
}
