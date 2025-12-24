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

namespace AthosCommerce\Feed\Api\Data;

interface ConfigItemInterface
{
    public const STORE_CODE = 'storeCode';
    public const SITE_ID = 'siteId';
    public const END_POINT = 'endPoint';
    public const SECRET_KEY = 'secretKey';
    public const SHOP_DOMAIN = 'shopDomain';
    public const FEED_ID = 'feedId';

    public const ENABLE_LIVE_INDEXING = 'enableLiveIndexing';
    public const ENTITY_SYNC_CRON_EXPR = 'entitySyncCronExpr';
    public const PER_MINUTE = 'perMinute';
    public const CHUNK_SIZE = 'chunkSize';


    /**
     * @return string
     */
    public function getStoreCode(): string;

    /**
     * @param string $storeCode
     */
    public function setStoreCode(string $storeCode): self;

    /**
     * @return string|null
     */
    public function getSiteId(): ?string;

    /**
     * @param string|null $siteId
     */
    public function setSiteId(?string $siteId): self;

    /**
     * @return string|null
     */
    public function getEndPoint(): ?string;

    /**
     * @param string|null $value
     */
    public function setEndPoint(?string $value): self;

    /**
     * @return string|null
     */
    public function getSecretKey(): ?string;

    /**
     * @param string|null $secretKey
     */
    public function setSecretKey(?string $secretKey): self;

    /**
     * @return string|null
     */
    public function getShopDomain(): ?string;

    /**
     * @param string|null $value
     */
    public function setShopDomain(?string $value): self;

    /**
     * @return int|null
     */
    public function getFeedId(): ?int;

    /**
     * @param int|null $value
     */
    public function setFeedId(?int $value): self;

    /**
     * @return int|null
     */
    public function getEnableLiveIndexing(): ?int;

    /**
     * @param int|null $value
     */
    public function setEnableLiveIndexing(?int $value): self;

    /**
     * @return string|null
     */
    public function getEntitySyncCronExpr(): ?string;

    /**
     * @param string|null $value
     */
    public function setEntitySyncCronExpr(?string $value): self;

    /**
     * @return int|null
     */
    public function getPerMinute(): ?int;

    /**
     * @param int|null $value
     */
    public function setPerMinute(?int $value): self;

    /**
     * @return int|null
     */
    public function getChunkSize(): ?int;

    /**
     * @param int|null $value
     */
    public function setChunkSize(?int $value): self;

}
