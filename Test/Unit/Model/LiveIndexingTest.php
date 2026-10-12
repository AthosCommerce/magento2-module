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

namespace AthosCommerce\Feed\Test\Unit\Model;

use AthosCommerce\Feed\Exception\LiveIndexingStoresFailedException;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Config;
use AthosCommerce\Feed\Model\LiveIndexing;
use AthosCommerce\Feed\Model\LiveIndexing\Processor;
use AthosCommerce\Feed\Model\LiveIndexing\StoreLock;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AthosCommerce\Feed\Model\LiveIndexing
 */
class LiveIndexingTest extends TestCase
{
    /**
     * @var Processor|MockObject
     */
    private $processor;
    /**
     * @var StoreLock|MockObject
     */
    private $storeLock;
    /**
     * @var LiveIndexing
     */
    private $liveIndexing;

    protected function setUp(): void
    {
        $stores = ['default' => $this->store(1, 'default'), 'second' => $this->store(2, 'second')];
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturnCallback(static fn (string $code) => $stores[$code]);
        $config = $this->createMock(Config::class);
        $config->method('getEndpointByStoreId')->willReturn('https://example.test');
        $config->method('isLiveIndexingEnabled')->willReturn(true);
        $config->method('getSiteIdByStoreId')->willReturnCallback(static fn (int $id): string => 'site-' . $id);
        $this->processor = $this->createMock(Processor::class);
        $this->storeLock = $this->createMock(StoreLock::class);
        $this->storeLock->method('acquire')->willReturn(true);

        $this->liveIndexing = new LiveIndexing(
            $storeManager,
            $config,
            $this->processor,
            $this->createMock(AthosCommerceLogger::class),
            $this->storeLock
        );
    }

    /**
     * A failing store does not stop the next one; the run then fails, naming only the failed store.
     */
    public function testFailedStoreIsReportedAfterTheOtherStoresRan(): void
    {
        $processed = [];
        $this->processor->method('execute')->willReturnCallback(
            static function (Store $store, string $siteId) use (&$processed): int {
                $processed[] = $store->getCode();
                if ($store->getCode() === 'default') {
                    throw new \RuntimeException('endpoint unreachable');
                }
                return 5;
            }
        );
        $released = [];
        $this->storeLock->method('release')->willReturnCallback(
            static function (string $type, string $storeCode) use (&$released): void {
                $released[] = $storeCode;
            }
        );

        try {
            $this->liveIndexing->execute(['default', 'second']);
            $this->fail('A failed store must be reported');
        } catch (LiveIndexingStoresFailedException $exception) {
            $this->assertSame(['default' => 'endpoint unreachable'], $exception->getFailures());
            $this->assertStringContainsString('Entity sync failed for 1 store(s)', $exception->getMessage());
        }
        $this->assertSame(['default', 'second'], $processed, 'The second store must still be processed');
        $this->assertSame(['default', 'second'], $released, 'Both store locks must be released');
    }

    /**
     * Without failures the per-store counts are returned as before.
     */
    public function testSuccessfulRunReturnsCounts(): void
    {
        $this->processor->method('execute')->willReturn(3);

        $this->assertSame(['default' => 3, 'second' => 3], $this->liveIndexing->execute(['default', 'second']));
    }

    /**
     * @param int $id
     * @param string $code
     * @return Store|MockObject
     */
    private function store(int $id, string $code)
    {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn($id);
        $store->method('getCode')->willReturn($code);

        return $store;
    }
}
