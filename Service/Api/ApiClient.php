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
use Psr\Log\LoggerInterface;
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
     * @var LoggerInterface
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
     * @param LoggerInterface $logger
     * @param StoreContextManager $storeContextManager
     * @param ConfigModel $config
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        ClientInterface $client,
        LoggerInterface $logger,
        StoreContextManager $storeContextManager,
        ConfigModel $config,
        JsonSerializer $jsonSerializer
    ) {
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
        array $payload,
        string $topic
    ): bool {
        $store = $this->storeContextManager->getStoreFromContext();
        $storeId = (int)$store->getId();
        $siteId = $this->config->getSiteIdByStoreId($storeId);
        $shopDomain = $this->config->getShopDomainByStoreId($storeId);
        $secretKey = $this->config->getSecretKeyByStoreId($storeId);
        $endpoint = $this->config->getEndpointByStoreId($storeId);
        $jsonPayload = $this->jsonSerializer->serialize($payload);

        $hmac = base64_encode(
            hash_hmac('sha256', $jsonPayload, $secretKey, true)
        );

        $headers = [
            'Content-Type' => 'application/json',
            'X-Topic' => $topic,
            'X-Shop-Domain' => $shopDomain,
            'X-Hmac-Sha256' => $hmac,
        ];
        $options = [];
        $this->client->setHeaders($headers);

        $this->logger->info(
            sprintf("Sending (%s) API Request", $topic),
            [
                'endpoint' => $endpoint,
                'siteId' => $siteId,
                'headers' => $headers,
                'payload' => $payload,

            ]
        );
        $this->client->setOptions($options);

        $this->client->post($endpoint, $jsonPayload);
        $responseBody = $this->client->getBody();
        /*$result = $this->jsonSerializer->unserialize(
            $responseBody
        );*/

        $httpStatusCode = $this->client->getStatus();

        $this->logger->info(
            sprintf("Received response for topic(%s)", $topic),
            [
                'httpStatusCode' => $httpStatusCode,
                'endpoint' => $endpoint,
                'siteId' => $siteId,
                'responseBody' => $responseBody
            ]
        );

        if ($httpStatusCode >= 200 && $httpStatusCode < 300) {
            return true;
        }

        $this->logger->warning("HTTP $httpStatusCode | Response: $responseBody");

        return false;
    }

    /**
     * @param array $headers
     *
     * @return array
     */
    private function applyMask(array $headers)
    {
        try {
            $headers = str_replace($this->maskFields, '***********', $header);
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf("Exception while masking: %s", $e->getMessage())
            );
        }

        return $headers;
    }
}
