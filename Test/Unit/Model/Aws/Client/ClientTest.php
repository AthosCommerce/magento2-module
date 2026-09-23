<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Model\Aws\Client;

require_once dirname(__DIR__, 3) . '/_files/bootstrap-stubs.php';

use AthosCommerce\Feed\Exception\ClientException;
use AthosCommerce\Feed\Helper\S3UrlValidator;
use AthosCommerce\Feed\Model\Aws\Client\Client;
use AthosCommerce\Feed\Model\Aws\Client\ResponseInterface;
use AthosCommerce\Feed\Model\Aws\Client\ResponseInterfaceFactory;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\RequestOptions;
use Magento\Framework\HTTP\AsyncClient\RequestFactory;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\StreamInterface;

class ClientTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     */
    public function testExecuteDisablesRedirects(): void
    {
        $requestFactoryMock = $this->createMock(RequestFactory::class);
        $responseFactoryMock = $this->createMock(ResponseInterfaceFactory::class);
        $s3UrlValidatorMock = $this->createMock(S3UrlValidator::class);
        $convertedResponseMock = $this->createMock(ResponseInterface::class);
        $bodyStreamMock = $this->createMock(StreamInterface::class);
        $bodyStreamMock->method('getContents')->willReturn('');
        $psrResponseMock = $this->createMock(PsrResponseInterface::class);
        $psrResponseMock->method('getStatusCode')->willReturn(200);
        $psrResponseMock->method('getHeaders')->willReturn(['content-type' => ['application/json']]);
        $psrResponseMock->method('getBody')->willReturn($bodyStreamMock);
        $s3UrlValidatorMock->expects($this->once())
            ->method('validate')
            ->with('https://test-athos-feed-bucket.s3.us-east-1.amazonaws.com/file.json.gz')
            ->willReturn(true);

        $guzzleClientMock = $this->createMock(GuzzleClient::class);
        $guzzleClientMock->expects($this->once())
            ->method('requestAsync')
            ->with(
                'PUT',
                'https://test-athos-feed-bucket.s3.us-east-1.amazonaws.com/file.json.gz',
                $this->callback(function (array $options): bool {
                    $this->assertSame(['Content-Type' => 'application/json'], $options[RequestOptions::HEADERS]);
                    $this->assertSame(false, $options[RequestOptions::ALLOW_REDIRECTS]);
                    $this->assertSame('payload', $options[RequestOptions::BODY]);

                    return true;
                })
            )
            ->willReturn(
                Create::promiseFor($psrResponseMock)
            );

        $responseFactoryMock->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $data): bool {
                $this->assertSame(200, $data['code']);
                $this->assertSame(['content-type' => 'application/json'], $data['headers']);
                $this->assertSame('', $data['body']);

                return true;
            }))
            ->willReturn($convertedResponseMock);

        $client = new Client($guzzleClientMock, $requestFactoryMock, $responseFactoryMock, $s3UrlValidatorMock);

        $this->assertSame(
            $convertedResponseMock,
            $client->execute(
                'PUT',
                'https://test-athos-feed-bucket.s3.us-east-1.amazonaws.com/file.json.gz',
                ['content' => 'payload'],
                ['Content-Type' => 'application/json']
            )
        );
    }

    /**
     * @return void
     */
    public function testExecuteRejectsNonS3Urls(): void
    {
        $client = new Client(
            $this->createMock(GuzzleClient::class),
            $this->createMock(RequestFactory::class),
            $this->createMock(ResponseInterfaceFactory::class),
            new S3UrlValidator()
        );

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Only HTTPS Amazon S3 bucket URLs are allowed for uploads.');

        $client->execute('PUT', 'https://fakeamazonaws.com/file.json.gz');
    }
}
