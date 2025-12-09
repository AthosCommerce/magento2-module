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

use AthosCommerce\Feed\Model\Feed\Context\StoreContextManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
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
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @param ClientInterface $client
     * @param LoggerInterface $logger
     * @param StoreContextManager $storeContextManager
     * @param ScopeConfigInterface $scopeConfig
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        ClientInterface $client,
        LoggerInterface $logger,
        StoreContextManager $storeContextManager,
        ScopeConfigInterface $scopeConfig,
        JsonSerializer $jsonSerializer
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->storeContextManager = $storeContextManager;
        $this->scopeConfig = $scopeConfig;
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
        $storeId = $store->getId();
        $shopDomain = $this->scopeConfig->getValue(
            Constants::XML_PATH_CONFIG_SHOP_DOMAIN,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
        $secretKey = $this->scopeConfig->getValue(
            Constants::XML_PATH_CONFIG_SECRET_KEY,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
        $endpoint = $this->scopeConfig->getValue(
            Constants::XML_PATH_CONFIG_ENDPOINT,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
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

        array_walk($headers, function (&$value, $key) {
            $value = ($value !== null && $value !== false)
                ? sprintf("%s: %s", $key, $value)
                : null;
            if (in_array($key, $this->maskFields)) {
                $value = sprintf("%s: %s", $key, '***************');
            }
        });
        $this->logger->info('Sending API Request', [
            'headers' => $headers,
            'endpoint' => $endpoint,
            'payload' => $payload,
            'topic' => $topic,
        ]);
        $this->client->setTimeout(20);
        $this->client->setOptions($options);

        $this->client->post($endpoint, $jsonPayload);
        $responseBody = $this->client->getBody();
        /*$result = $this->jsonSerializer->unserialize(
            $responseBody
        );*/

        $code = $this->client->getStatus();

        $this->logger->info(
            'API Response',
            [
                'code' => $code,
                'responseBody' => $responseBody,
                'topic' => $topic,
            ]
        );


        if ($code >= 200 && $code < 300) {
            return true;
        }

        $this->logger->warning("HTTP $code | Response: $responseBody");

        return false;
    }

    /**
     * @param array $headers
     *
     * @return array
     */
    private function applyMask(array $headers) {
        try {
            foreach ($headers as $header) {
               $header =  strtolower($header);
                switch ($header) {
                    case 'X-Hmac-Sha256':
                        $header = str_replace($header, '***********', $header);
                        break;
                    default:
                        break;

                }
            }
        }catch (\Exception $e) {
            $this->logger->error(
                sprintf("Exception while masking: %s", $e->getMessage())
            );
        }
        return $headers;
    }
}
