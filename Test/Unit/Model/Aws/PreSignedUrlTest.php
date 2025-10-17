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

namespace AthosCommerce\Feed\Test\Unit\Model\Aws;

use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Api\AppConfigInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Aws\Client\ClientInterface;
use AthosCommerce\Feed\Model\Aws\Client\ResponseInterface;
use AthosCommerce\Feed\Model\Aws\PreSignedUrl;

class PreSignedUrlTest extends TestCase
{
    private $clientMock;

    private $appConfigMock;

    private $preSignedUrl;

    public function setUp(): void
    {
        $this->clientMock = $this->createMock(ClientInterface::class);
        $this->appConfigMock = $this->createMock(AppConfigInterface::class);
        $this->preSignedUrl = new PreSignedUrl(
            $this->clientMock,
            $this->appConfigMock,
            [],
            [],
            5,
            30
        );
    }

    public function testSave()
    {
        $responseInterfaceMock = $this->getMockForAbstractClass(ResponseInterface::class);
        $responseInterfaceMock->expects($this->once())
            ->method('getCode')
            ->willReturn(200);
        $feedSpecificationMock = $this->getFeedSpecificationMock();
        $this->clientMock->expects($this->once())
            ->method('execute')
            ->withAnyParameters()
            ->willReturn($responseInterfaceMock);

        $this->preSignedUrl->save($feedSpecificationMock, ['test']);
    }

    /**
     * @return void
     */
    public function testSaveExceptionCase()
    {
        $feedSpecificationMock = $this->getFeedSpecificationMock();
        $this->expectException(\Exception::class);
        $this->preSignedUrl->save($feedSpecificationMock, ['test']);
    }

    private function getFeedSpecificationMock()
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getPreSignedUrl')
            ->willReturn('test');

        return $feedSpecificationMock;
    }
}
