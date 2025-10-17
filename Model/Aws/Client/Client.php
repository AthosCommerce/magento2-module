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

namespace AthosCommerce\Feed\Model\Aws\Client;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\RequestOptions;
use Magento\Framework\HTTP\AsyncClient\GuzzleWrapDeferred;
use Magento\Framework\HTTP\AsyncClient\RequestFactory;
use Psr\Http\Message\StreamInterface;
use AthosCommerce\Feed\Exception\ClientException;
use Throwable;

class Client implements ClientInterface
{
    /**
     * @var RequestFactory
     */
    private $requestFactory;
    /**
     * @var ResponseInterfaceFactory
     */
    private $responseFactory;
    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * Client constructor.
     * @param GuzzleClient $client
     * @param RequestFactory $requestFactory
     * @param ResponseInterfaceFactory $responseFactory
     */
    public function __construct(
        GuzzleClient $client,
        RequestFactory $requestFactory,
        ResponseInterfaceFactory $responseFactory
    ) {
        $this->requestFactory = $requestFactory;
        $this->responseFactory = $responseFactory;
        $this->client = $client;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array|null $content
     * @param array $headers
     * @return ResponseInterface
     * @throws ClientException
     */
    public function execute(string $method, string $url, ?array $content = null, array $headers = []) : ResponseInterface
    {
        if ($content) {
            $content = $this->prepareContent($content);
        }

        try {
            $options = [];
            $options[RequestOptions::HEADERS] = $headers;
            if ($content !== null) {
                $options[RequestOptions::BODY] = $content;
            }

            $responseWrapper = new GuzzleWrapDeferred(
                $this->client->requestAsync(
                    $method,
                    $url,
                    $options
                )
            );
            $response = $responseWrapper->get();
            $convertedResponse = $this->responseFactory->create([
                'code' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body' => $response->getBody()
            ]);
        } catch (Throwable $exception) {
            throw new ClientException($exception->getMessage(), 0, $exception);
        }

        return $convertedResponse;
    }

    /**
     * @param array $content
     * @return mixed|StreamInterface
     */
    private function prepareContent(array $content)
    {
        $type = $content['type'] ?? 'default';
        if ($type === 'stream' && isset($content['file'])) {
            $result = Utils::streamFor(Utils::tryFopen($content['file'], 'r+'));
        } else {
            $result = $content['content'] ?? array_shift($content);
        }

        return $result;
    }
}
