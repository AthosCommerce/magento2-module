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

namespace AthosCommerce\Feed\Model;

use AthosCommerce\Feed\Helper\Constants;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class Config
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
    }

    /**
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isLiveIndexingEnabled(?int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            Constants::XML_PATH_LIVE_INDEXING_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     *
     * @return string
     */
    public function getSiteIdByStoreId(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            Constants::XML_PATH_CONFIG_SITE_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     *
     * @return string
     */
    public function getEndpointByStoreId(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            Constants::XML_PATH_CONFIG_ENDPOINT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     *
     * @return string
     */
    public function getSecretKeyByStoreId(?int $storeId = null): string
    {
        $secretKey = (string)$this->scopeConfig->getValue(
            Constants::XML_PATH_CONFIG_SECRET_KEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $this->encryptor->decrypt($secretKey);
    }

    /**
     * @param int|null $storeId
     *
     * @return string
     */
    public function getShopDomainByStoreId(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            Constants::XML_PATH_CONFIG_SHOP_DOMAIN,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getRequestPerMinuteByStoreId(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            Constants::XML_PATH_LIVE_INDEXING_PER_MINUTE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?? Constants::DEFAULT_MAX_REQUEST_LIMIT;
    }

    /**
     * @param int|null $storeId
     *
     * @return string
     */
    public function getChunkSizeByStoreId(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            Constants::XML_PATH_LIVE_INDEXING_CHUNK_PER_SIZE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?? 100;
    }

    /**
     * @param int|null $storeId
     *
     * @return string
     */
    public function getPayloadByStoreId(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            Constants::XML_PATH_LIVE_INDEXING_TASK_PAYLOAD,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     *
     * @return int
     */
    public function getMillisecondsDelayByStoreId(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            Constants::XML_PATH_LIVE_INDEXING_MILLISECONDS_DELAY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?? 50;
    }
}
