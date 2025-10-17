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

namespace AthosCommerce\Feed\Model\Aws;

use AthosCommerce\Feed\Api\AppConfigInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Exception\ClientException;
use AthosCommerce\Feed\Model\Aws\Client\ClientInterface;
use AthosCommerce\Feed\Model\Aws\Client\ResponseInterface;

class PreSignedUrl
{
    /**
     * @var ClientInterface
     */
    private $client;
    /**
     * @var array
     */
    private $retryCodes = [500];
    /**
     * @var array
     */
    private $successCodes = [];
    /**
     * @var int
     */
    private $retryCount;
    /**
     * @var int
     */
    private $repeatDelay;
    /**
     * @var AppConfigInterface
     */
    private $appConfig;

    /**
     * PreSignedUrl constructor.
     * @param ClientInterface $client
     * @param AppConfigInterface $appConfig
     * @param array $retryCodes
     * @param array $successCodes
     * @param int $retryCount
     * @param int $repeatDelay
     */
    public function __construct(
        ClientInterface $client,
        AppConfigInterface $appConfig,
        array $retryCodes = [],
        array $successCodes = [],
        int $retryCount = 5,
        int $repeatDelay = 30
    ) {
        $this->client = $client;
        $this->retryCodes = array_merge($this->retryCodes, $retryCodes);
        $this->successCodes = array_merge($this->successCodes, $successCodes);
        $this->retryCount = $retryCount;
        $this->repeatDelay = $repeatDelay;
        $this->appConfig = $appConfig;
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     * @param array $content
     * @throws \Exception
     */
    public function save(FeedSpecificationInterface $feedSpecification, array $content) : void
    {
        if ($this->appConfig->isDebug()
            && !is_null($this->appConfig->getValue('product_api_mock'))
            && $this->appConfig->getValue('product_api_mock')
        ) {
            return;
        }
        $url = $feedSpecification->getPreSignedUrl();
        if (!$url) {
            throw new \Exception();
        }

        $this->doRequest($url, [], $content);
    }

    /**
     * @param string $url
     * @param array $headers
     * @param array $content
     * @throws \Exception
     */
    private function doRequest(string $url, array $headers, array $content) : void
    {
        $retry = true;
        $lastError = null;
        $lastErrorMessage = null;
        $success = false;
        $retryCount = 0;
        while ($retry) {
            $response = null;
            try {
                $response = $this->client->execute('PUT', $url, $content, $headers);
            } catch (ClientException $exception) {
                $retry = false;
                $lastError = $exception;
                $lastErrorMessage = $exception->getMessage();
            }

            if ($response) {
                if ($this->isSuccess($response)) {
                    $success = true;
                    $retry = false;
                } elseif ($this->isRepeatableError($response)) {
                    $retryCount += 1;
                    if ($retryCount < $this->retryCount) {
                        $retry = false;
                        $lastErrorMessage = $this->getErrorMessageFromResponse($response, $url);
                    } else {
                        sleep($this->repeatDelay);
                    }
                } else {
                    $retry = false;
                    $lastErrorMessage = $this->getErrorMessageFromResponse($response, $url);
                }
            }
        }

        if (!$success) {
            throw new \Exception($lastErrorMessage, 0, $lastError);
        }
    }

    /**
     * @param ResponseInterface $response
     * @param string $url
     * @return string
     */
    private function getErrorMessageFromResponse(ResponseInterface $response, string $url) : string
    {
        $statusCode = $response->getCode();
        $errorMessage = $response->getBody()
            ? 'error message: ' . $response->getBody()
            : 'no error message';
        $fullMessage = (string) __(
            'Cannot save file by pre-signed url %1, response code %2, %3',
            $url,
            $statusCode,
            $errorMessage
        );

        return $fullMessage;
    }

    /**
     * @param ResponseInterface $response
     * @return bool
     */
    private function isSuccess(ResponseInterface $response) : bool
    {
        $statusCode = $response->getCode();
        return ($statusCode >= 200 && $statusCode < 300) || in_array($statusCode, $this->successCodes);
    }

    /**
     * @param ResponseInterface $response
     * @return bool
     */
    private function isRepeatableError(ResponseInterface $response) : bool
    {
        $statusCode = $response->getCode();
        return in_array($statusCode, $this->retryCodes);
    }
}
