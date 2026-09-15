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
            $encryptorMock
        );

        $rawSecret = 'plain:text-secret';

        $configRepositoryMock->expects($this->once())
            ->method('fetchStoreConfigRows')
            ->willReturn([
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
            ->method('getStore')
            ->with(1)
            ->willReturn($storeMock);
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
}
