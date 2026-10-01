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

use AthosCommerce\Feed\Api\Data\ConfigInfoResponseInterface;
use AthosCommerce\Feed\Api\Data\StoreConfigInterface;
use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Api\GetConfigInfo;
use AthosCommerce\Feed\Model\Config\StoreConfigApiMapper;
use AthosCommerce\Feed\Model\ConfigRepository;
use AthosCommerce\Feed\Model\Data\ConfigInfoResponseFactory;
use AthosCommerce\Feed\Model\Data\StoreConfigFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class GetConfigInfoTest extends TestCase
{
    public function testGetRetainsPlaintextSecretContainingColonWhenDecryptReturnsEmpty(): void
    {
        $configRepositoryMock = $this->createMock(ConfigRepository::class);
        $storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $loggerMock = $this->createMock(AthosCommerceLogger::class);
        $responseFactoryMock = $this->createMock(ConfigInfoResponseFactory::class);
        $storeConfigFactoryMock = $this->createMock(StoreConfigFactory::class);
        $storeConfigApiMapperMock = $this->createMock(StoreConfigApiMapper::class);
        $encryptorMock = $this->createMock(EncryptorInterface::class);
        $responseMock = $this->createMock(ConfigInfoResponseInterface::class);
        $storeConfigMock = $this->createMock(StoreConfigInterface::class);
        $storeMock = $this->createMock(StoreInterface::class);

        $model = new GetConfigInfo(
            $configRepositoryMock,
            $storeManagerMock,
            $loggerMock,
            $responseFactoryMock,
            $storeConfigFactoryMock,
            $storeConfigApiMapperMock,
            $encryptorMock,
            $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class)
        );

        $rawSecret = 'plain:text-secret';

        $configRepositoryMock->expects($this->once())
            ->method('fetchStoreConfigRows')
            ->willReturn([
                [
                    'scope_id' => 1,
                    'path' => Constants::XML_PATH_DEBUG_LOG_ENABLED,
                    'value' => '0',
                ],
                [
                    'scope_id' => 1,
                    'path' => Constants::XML_PATH_CONFIG_SECRET_KEY,
                    'value' => $rawSecret,
                ],
            ]);

        $encryptorMock->expects($this->once())
            ->method('decrypt')
            ->with($rawSecret)
            ->willReturn('');

        $storeManagerMock->expects($this->once())
            ->method('getStores')
            ->with(false)
            ->willReturn([$storeMock]);
        $storeMock->method('getId')->willReturn(1);
        $storeMock->expects($this->once())
            ->method('getCode')
            ->willReturn('default');

        $responseFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($responseMock);
        $storeConfigFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($storeConfigMock);

        $storeConfigMock->expects($this->once())->method('setStoreId')->with(1)->willReturnSelf();
        $storeConfigMock->expects($this->once())->method('setStoreCode')->with('default')->willReturnSelf();
        $storeConfigMock->expects($this->once())->method('setEnableDebugLog')->with(false)->willReturnSelf();
        $storeConfigMock->expects($this->once())->method('setSecretKey')->with($rawSecret)->willReturnSelf();
        $storeConfigMock->expects($this->once())->method('setSecretKeyLength')->with(strlen($rawSecret))->willReturnSelf();

        $storeConfigApiMapperMock->expects($this->once())
            ->method('map')
            ->with($storeConfigMock)
            ->willReturn(['storeId' => 1, 'secretKeyLength' => strlen($rawSecret)]);

        $responseMock->expects($this->once())->method('setSuccess')->with(true)->willReturnSelf();
        $responseMock->expects($this->once())
            ->method('setMessage')
            ->with('Configuration fetched successfully.')
            ->willReturnSelf();
        $responseMock->expects($this->once())
            ->method('setStores')
            ->with([['storeId' => 1, 'secretKeyLength' => strlen($rawSecret)]])
            ->willReturnSelf();

        $loggerMock->method('info');

        $result = $model->get();

        $this->assertSame($responseMock, $result);
    }

    /**
     * Without any store-scope row every store view is still reported, with the global cron schedules.
     */
    public function testGetReportsEveryStoreWithGlobalSchedulesWhenNoStoreRows(): void
    {
        $configRepositoryMock = $this->createMock(ConfigRepository::class);
        $configRepositoryMock->method('fetchStoreConfigRows')->willReturn([]);
        $storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $storeManagerMock->method('getStores')->with(false)->willReturn([
            $this->createStore(1, 'default'),
            $this->createStore(2, 'second'),
        ]);
        $scopeConfigMock = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $scopeConfigMock->method('getValue')->willReturnMap([
            [Constants::XML_PATH_LIVE_INDEXING_SYNC_CRON_EXPR, 'default', null, '*/5 * * * *'],
            [Constants::XML_PATH_LIVE_INDEXING_DISCOVERY_CRON_EXPR, 'default', null, '0 17 * * *'],
        ]);
        $storeConfigFactoryMock = $this->createMock(StoreConfigFactory::class);
        $storeConfigFactoryMock->method('create')->willReturnCallback(
            function () {
                $storeConfig = $this->createMock(StoreConfigInterface::class);
                $storeConfig->expects($this->once())->method('setEntitySyncCronExpr')->with('*/5 * * * *');
                $storeConfig->expects($this->once())->method('setDiscoverySyncCronExpr')->with('0 17 * * *');
                return $storeConfig;
            }
        );
        $created = [];
        $storeConfigApiMapperMock = $this->createMock(StoreConfigApiMapper::class);
        $storeConfigApiMapperMock->method('map')->willReturnCallback(
            static function ($storeConfig) use (&$created): array {
                $created[] = $storeConfig;
                return ['store' => count($created)];
            }
        );
        $responseMock = $this->createMock(ConfigInfoResponseInterface::class);
        $responseMock->expects($this->once())->method('setSuccess')->with(true)->willReturnSelf();
        $responseMock->expects($this->once())
            ->method('setMessage')
            ->with('Configuration fetched successfully.')
            ->willReturnSelf();
        $responseMock->expects($this->once())
            ->method('setStores')
            ->with([['store' => 1], ['store' => 2]])
            ->willReturnSelf();
        $responseFactoryMock = $this->createMock(ConfigInfoResponseFactory::class);
        $responseFactoryMock->method('create')->willReturn($responseMock);

        $model = new GetConfigInfo(
            $configRepositoryMock,
            $storeManagerMock,
            $this->createMock(AthosCommerceLogger::class),
            $responseFactoryMock,
            $storeConfigFactoryMock,
            $storeConfigApiMapperMock,
            $this->createMock(EncryptorInterface::class),
            $scopeConfigMock
        );

        $this->assertSame($responseMock, $model->get());
        $this->assertCount(2, $created);
    }

    /**
     * @param int $id
     * @param string $code
     * @return StoreInterface
     */
    private function createStore(int $id, string $code): StoreInterface
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn($id);
        $store->method('getCode')->willReturn($code);

        return $store;
    }
}
