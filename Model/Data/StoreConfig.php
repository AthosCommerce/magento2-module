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

namespace AthosCommerce\Feed\Model\Data;

use AthosCommerce\Feed\Api\Data\StoreConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractExtensibleModel;

class StoreConfig extends AbstractExtensibleModel implements StoreConfigInterface
{
    /**
     * @return int
     */
    public function getStoreId(): int
    {
        return (int)$this->getData(self::STORE_ID);
    }

    /**
     * @param int $storeId
     */
    public function setStoreId(int $storeId): self
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @return string
     */
    public function getStoreCode(): string
    {
        return (string)$this->getData(self::STORE_CODE);
    }

    /**
     * @param string $storeCode
     */
    public function setStoreCode(string $storeCode): self
    {
        return $this->setData(self::STORE_CODE, $storeCode);
    }

    /**
     * @return string|null
     */
    public function getSiteId(): ?string
    {
        return $this->getData(self::SITE_ID);
    }

    /**
     * @param string|null $siteId
     */
    public function setSiteId(?string $siteId): self
    {
        return $this->setData(self::SITE_ID, $siteId);
    }

    /**
     * @return string|null
     */
    public function getEndPoint(): ?string
    {
        return $this->getData(self::END_POINT);
    }

    /**
     * @param string|null $endPoint
     */
    public function setEndPoint(?string $endPoint): self
    {
        return $this->setData(self::END_POINT, $endPoint);
    }

    /**
     * @return string|null
     */
    public function getSecretKey(): ?string
    {
        return $this->getData(self::SECRET_KEY);
    }

    /**
     * @param string|null $secretKey
     */
    public function setSecretKey(?string $secretKey): self
    {
        return $this->setData(self::SECRET_KEY, $secretKey);
    }

    /**
     * @return int|null
     */
    public function getEnableLiveIndexing(): ?int
    {
        return $this->getData(self::ENABLE_LIVE_INDEXING);
    }

    /**
     * @param int|null $enableLiveIndexing
     */
    public function setEnableLiveIndexing(?int $enableLiveIndexing): self
    {
        return $this->setData(self::ENABLE_LIVE_INDEXING, $enableLiveIndexing);
    }

    /**
     * @return string|null
     */
    public function getEntitySyncCronExpr(): ?string
    {
        return $this->getData(self::ENTITY_SYNC_CRON_EXPR);
    }

    /**
     * @param string|null $entitySyncCronExpr
     */
    public function setEntitySyncCronExpr(?string $entitySyncCronExpr): self
    {
        return $this->setData(self::ENTITY_SYNC_CRON_EXPR, $entitySyncCronExpr);
    }

    /**
     * @return int|null
     */
    public function getPerMinute(): ?int
    {
        return $this->getData(self::PER_MINUTE);
    }

    /**
     * @param int|null $perMinute
     */
    public function setPerMinute(?int $perMinute): self
    {
        return $this->setData(self::PER_MINUTE, $perMinute);
    }

    /**
     * @return int|null
     */
    public function getChunkSize(): ?int
    {
        return $this->getData(self::CHUNK_SIZE);
    }

    /**
     * @param int|null $chunkSize
     */
    public function setChunkSize(?int $chunkSize): self
    {
        return $this->setData(self::CHUNK_SIZE, $chunkSize);
    }

    /**
     * @return array
     */
    public function toArray(array $keys = []): array
    {
        return [
            'storeId' => $this->getStoreId(),
            'storeCode' => $this->getStoreCode(),
            'siteId' => $this->getSiteId(),
            'secretKey' => $this->getSecretKey(),
            'endPoint' => $this->getEndPoint(),
            'enableLiveIndexing' => $this->getEnableLiveIndexing(),
            'entitySyncCronExpr' => $this->getEntitySyncCronExpr(),
            'perMinute' => $this->getPerMinute(),
            'chunkSize' => $this->getChunkSize(),
        ];
    }
}
