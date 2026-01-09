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

namespace AthosCommerce\Feed\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use AthosCommerce\Feed\Api\ConfigInterface;

/**
 * Class Config
 *
 * This class gets AthosCommerce site id which entered from admin panel
 *
 * @package AthosCommerce\Feed\Service
 */
class Config implements ConfigInterface
{
    /**
     * Config path for AthosCommerce site id
     * @deprecated
     */
    public const ATHOSCOMMERCE_SITE_ID = 'athoscommerce/general/site_id';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ScopeConfigInterface  $scopeConfig,
        StoreManagerInterface $storeManager
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getSiteId(?int $storeId = null): ?string
    {
        return (string)$this->scopeConfig->getValue(
            self::ATHOSCOMMERCE_SITE_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
