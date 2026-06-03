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

namespace AthosCommerce\Feed\Service\Provider;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class StoreProvider
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param StoreManagerInterface $storeManager
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        AthosCommerceLogger   $logger
    )
    {
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * @param string|null $storeCode
     * @return StoreInterface|null
     */
    public function getStore(?string $storeCode = null): ?StoreInterface
    {
        try {
            return $this->storeManager->getStore($storeCode);
        } catch (\Throwable $e) {
            $this->logger->error('StoreException' . $e->getMessage());
            return null;
        }
    }

    /**
     * @param string|null $storeCode
     * @return int|null
     */
    public function getStoreId(?string $storeCode = null): ?int
    {
        try {
            $storeId = (int)$this->storeManager->getStore($storeCode)->getId();
            return $storeId;
        } catch (\Throwable $e) {
            $this->logger->error('StoreException' . $e->getMessage());
            return null;
        }
    }
}
