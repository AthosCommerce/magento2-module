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

namespace AthosCommerce\Feed\Service\Api;

use AthosCommerce\Feed\Model\Config as ConfigModel;
use AthosCommerce\Feed\Model\Feed\Context\StoreContextManager;
use Magento\Framework\Serialize\SerializerInterface as JsonSerializer;
use Magento\Framework\HTTP\ClientInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Helper\Constants;

class ApiClient
{
    /**
     * @var string[]
     */
    private $maskFields = ['X-Shop-Domain', 'X-Hmac-Sha256'];

    /**
     * @var ClientInterface
     */
    private $client;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;
    /**
     * @var StoreContextManager
     */
    private $storeContextManager;
    /**
     * @var ConfigModel
     */
    private $config;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @param ClientInterface $client
     * @param AthosCommerceLogger $logger
     * @param StoreContextManager $storeContextManager
     * @param ConfigModel $config
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        ClientInterface     $client,
        AthosCommerceLogger     $logger,
        StoreContextManager $storeContextManager,
        ConfigModel         $config,
        JsonSerializer      $jsonSerializer
    )
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->storeContextManager = $storeContextManager;
        $this->config = $config;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @param array $payload
     * @param string $topic
     *
     * @return bool
     */
    public function send(
        array  $payload,
        string $topic
    ): bool
    {
        $startTime = microtime(true);
        $store = $this->storeContextManager->getStoreFromContext();
        $storeId = (int)$store->getId();
        $storeCode = $store->getCode();
        $siteId = $this->config->getSiteIdByStoreId($storeId);
        $shopDomain = $this->config->getShopDomainByStoreId($storeId);
        $secretKey = $this->config->getSecretKeyByStoreId($storeId);
        $endpoint = rtrim($this->config->getEndpointByStoreId($storeId), '/');
        $feedId = ltrim($this->config->getFeedIdByStoreId($storeId), '/');

        $endpointUrl = $endpoint . '/' . $feedId;
        $jsonPayload = $this->jsonSerializer->serialize($payload);
        $sizeInBytes = strlen($jsonPayload);

        $hmac = base64_encode(
            hash_hmac('sha256', $jsonPayload, $secretKey, true)
        );

        $headers = [
            'Content-Type' => 'application/json',
            'X-Topic' => $topic,
            'X-Shop-Domain' => $shopDomain,
            'X-Hmac-Sha256' => $hmac,
        ];
        $maskedHeaders = $this->applyMask($headers);

        if ($sizeInBytes > (1024 * 1024)) {
            $this->logger->error(
                sprintf("[LiveIndexing] Payload exceeds limit for (%s)", $topic),
                [
                    'endpointUrl' => $endpointUrl,
                    'storeCode' => $storeCode,
                    'siteId' => $siteId,
                    'headers' => $maskedHeaders,
                    'payload' => $payload,
                    'length' => $sizeInBytes . ' bytes'
                ]
            );
            return false;
        }
        $options = [];
        $this->client->setHeaders($headers);

        $this->logger->info(
            sprintf("Initiating API request for %s", $topic),
            [
                'endpointUrl' => $endpointUrl,
                'siteId' => $siteId,
                'storeCode' => $storeCode,
                'endpoint' => $endpoint,
                'feedId' => $feedId,
                'shopDomain' => $shopDomain,
                'headers' => $maskedHeaders,
                'length' => $sizeInBytes . ' bytes',
                'payload' => $payload
            ]
        );
        $this->client->setOptions($options);

        $this->client->post($endpointUrl, $jsonPayload);
        $responseBody = $this->client->getBody();
        $endTime = microtime(true);
        $durationInSeconds = $endTime - $startTime;
        $httpStatusCode = $this->client->getStatus();
        $this->logger->info(
            sprintf("API Response status:%s | topic: %s", $httpStatusCode, $topic),
            [
                'endpointUrl' => $endpointUrl,
                'durationInSeconds' => $durationInSeconds,
                'siteId' => $siteId,
                'storeCode' => $storeCode,
                'storeCode' => $storeCode,
                'feedId' => $feedId,
                'shopDomain' => $shopDomain,
                'responseBody' => $responseBody,
            ]
        );

        if ($httpStatusCode >= 200 && $httpStatusCode < 300) {
            return true;
        }

        $this->logger->error("HTTP $httpStatusCode | Response: $responseBody");

        return false;
    }

    /**
     * @param array $values
     *
     * @return array
     */
    private function applyMask(array $values)
    {
        foreach ($this->maskFields as $field) {
            if (isset($values[$field])) {
                $values[$field] = substr($values[$field], 0, 6) . '******';
            }
        }
        return $values;
    }
}
