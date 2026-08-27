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

namespace AthosCommerce\Feed\Test\Unit\Service\Api;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Config;
use AthosCommerce\Feed\Model\Feed\Context\StoreContextManager;
use AthosCommerce\Feed\Service\Api\ApiClient;
use Magento\Framework\HTTP\ClientInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;

class ApiClientTest extends TestCase
{
    /**
     * @var ClientInterface
     */
    private $httpClientMock;

    /**
     * @var AthosCommerceLogger
     */
    private $loggerMock;

    /**
     * @var StoreContextManager
     */
    private $storeContextManagerMock;

    /**
     * @var Config
     */
    private $configModelMock;

    /**
     * @var SerializerInterface
     */
    private $jsonSerializerMock;

    /**
     * @var ApiClient
     */
    private $apiClient;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(ClientInterface::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);
        $this->storeContextManagerMock = $this->createMock(StoreContextManager::class);
        $this->configModelMock = $this->createMock(Config::class);
        $this->jsonSerializerMock = $this->createMock(SerializerInterface::class);

        $this->apiClient = new ApiClient(
            $this->httpClientMock,
            $this->loggerMock,
            $this->storeContextManagerMock,
            $this->configModelMock,
            $this->jsonSerializerMock
        );
    }

    public function testSendReturnsFalseWhenEndpointIsInvalid(): void
    {
        $storeMock = $this->createMock(Store::class);
        $storeMock->method('getId')->willReturn(1);
        $storeMock->method('getCode')->willReturn('default');

        $this->storeContextManagerMock->expects($this->once())
            ->method('getStoreFromContext')
            ->willReturn($storeMock);

        $this->configModelMock->expects($this->once())
            ->method('getSiteIdByStoreId')
            ->with(1)
            ->willReturn('site-id');
        $this->configModelMock->expects($this->once())
            ->method('getShopDomainByStoreId')
            ->with(1)
            ->willReturn('shop.example.com');
        $this->configModelMock->expects($this->once())
            ->method('getSecretKeyByStoreId')
            ->with(1)
            ->willReturn('secret');
        $this->configModelMock->expects($this->once())
            ->method('getEndpointByStoreId')
            ->with(1)
            ->willReturn('');

        $this->jsonSerializerMock->expects($this->never())->method('serialize');
        $this->httpClientMock->expects($this->never())->method('post');

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                '[LiveIndexing] Invalid endpoint configuration',
                $this->arrayHasKey('storeCode')
            );

        $this->assertFalse($this->apiClient->send(['a' => 1], 'topic'));
    }
}

