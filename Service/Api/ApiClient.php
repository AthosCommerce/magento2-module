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
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use AthosCommerce\Feed\Helper\Constants;

class ApiClient
{
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
     * @param LoggerInterface $logger
     * @param StoreContextManager $storeContextManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        LoggerInterface $logger,
        StoreContextManager $storeContextManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->storeContextManager = $storeContextManager;
        $this->scopeConfig = $scopeConfig;
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

        $jsonPayload = json_encode($payload);

        $hmac = base64_encode(
            hash_hmac('sha256', $jsonPayload, $secretKey, true)
        );

        $headers = [
            'Content-Type: application/json',
            'x-topic: ' . $topic,
            'x-shop-domain: ' . $shopDomain,
            'x-hmac-sha256: ' . $hmac,
        ];

        $this->logger->info('Sending API Request', [
            'headers' => $headers,
            'endpoint' => $endpoint,
            'payload' => $payload,
            'topic' => $topic,
        ]);

        $ch = curl_init($endpoint);
        curl_setopt_array(
            $ch,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $jsonPayload,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 20,
            ]);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->logger->info(
            'API Response',
            [
                'code' => $code,
                'response' => $response,
                'errorNo' => $errno,
                'topic' => $topic,
            ]
        );
        if ($errno) {
            $this->logger->error('Curl error: ' . $errno);

            return false;
        }

        if ($code >= 200 && $code < 300) {
            return true;
        }

        $this->logger->warning("HTTP $code | Response: $response");

        return false;
    }
}
