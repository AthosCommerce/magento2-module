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
        $plaintextSecretKey = (string)$this->scopeConfig->getValue(
            Constants::XML_PATH_CONFIG_SECRET_KEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $this->encryptor->decrypt($plaintextSecretKey);
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

    /**
     * @param int|null $storeId
     *
     * @return string
     */
    public function getBatchPerSizeByStoreId(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            Constants::XML_PATH_LIVE_INDEXING_BATCH_PER_SIZE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
