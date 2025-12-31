<?php

namespace AthosCommerce\Feed\Api\Data;

interface StoreConfigInterface
{
    public const STORE_ID = 'storeId';
    public const STORE_CODE = 'storeCode';
    public const SITE_ID = 'siteId';
    public const END_POINT = 'endPoint';
    public const SECRET_KEY = 'secretKey';

    public const ENABLE_LIVE_INDEXING = 'enableLiveIndexing';
    public const ENTITY_SYNC_CRON_EXPR = 'entitySyncCronExpr';
    public const PER_MINUTE = 'perMinute';
    public const CHUNK_SIZE = 'chunkSize';

    /**
     * @return int
     */
    public function getStoreId(): int;

    /**
     * @param int $storeId
     * @return self
     */
    public function setStoreId(int $storeId): self;

    /**
     * @return string
     */
    public function getStoreCode(): string;

    /**
     * @param string $storeCode
     * @return self
     */
    public function setStoreCode(string $storeCode): self;

    /**
     * @return string|null
     */
    public function getSiteId(): ?string;

    /**
     * @param string|null $siteId
     * @return self
     */
    public function setSiteId(?string $siteId): self;

    /**
     * @return string|null
     */
    public function getEndPoint(): ?string;

    /**
     * @param string|null $value
     * @return self
     */
    public function setEndPoint(?string $value): self;

    /**
     * @return string|null
     */
    public function getSecretKey(): ?string;

    /**
     * @param string|null $secretKey
     * @return self
     */
    public function setSecretKey(?string $secretKey): self;

    /**
     * @return int|null
     */
    public function getEnableLiveIndexing(): ?int;

    /**
     * @param int|null $value
     * @return self
     */
    public function setEnableLiveIndexing(?int $value): self;

    /**
     * @return string|null
     */
    public function getEntitySyncCronExpr(): ?string;

    /**
     * @param string|null $value
     * @return self
     */
    public function setEntitySyncCronExpr(?string $value): self;

    /**
     * @return int|null
     */
    public function getPerMinute(): ?int;

    /**
     * @param int|null $value
     * @return self
     */
    public function setPerMinute(?int $value): self;

    /**
     * @return int|null
     */
    public function getChunkSize(): ?int;

    /**
     * @param int|null $value
     * @return self
     */
    public function setChunkSize(?int $value): self;
}
