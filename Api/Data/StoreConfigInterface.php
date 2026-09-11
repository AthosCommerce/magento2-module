<?php

namespace AthosCommerce\Feed\Api\Data;

interface StoreConfigInterface
{
    public const STORE_ID = 'storeId';
    public const STORE_CODE = 'storeCode';
    public const SITE_ID = 'siteId';
    public const END_POINT = 'endPoint';
    public const SECRET_KEY = 'secretKey';
    public const SECRET_KEY_LENGTH = 'secretKeyLength';

    public const ENABLE_LIVE_INDEXING = 'enableLiveIndexing';
    public const ENTITY_SYNC_CRON_EXPR = 'entitySyncCronExpr';
    public const PER_MINUTE = 'perMinute';
    public const CHUNK_SIZE = 'chunkSize';

    public const TASK_PAYLOAD = 'taskPayload';

    /**
     * Get the store ID.
     *
     * @return int
     */
    public function getStoreId(): int;

    /**
     * Set the store ID.
     *
     * @param int $storeId
     * @return self
     */
    public function setStoreId(int $storeId): self;

    /**
     * Get the store code.
     *
     * @return string
     */
    public function getStoreCode(): string;

    /**
     * Set the store code.
     *
     * @param string $storeCode
     * @return self
     */
    public function setStoreCode(string $storeCode): self;

    /**
     * Get the site ID.
     *
     * @return string|null
     */
    public function getSiteId(): ?string;

    /**
     * Set the site ID.
     *
     * @param string|null $siteId
     * @return self
     */
    public function setSiteId(?string $siteId): self;

    /**
     * Get the endpoint.
     *
     * @return string|null
     */
    public function getEndPoint(): ?string;

    /**
     * Set the endpoint.
     *
     * @param string|null $value
     * @return self
     */
    public function setEndPoint(?string $value): self;

    /**
     * Get the secret key.
     *
     * @return string|null
     */
    public function getSecretKey(): ?string;

    /**
     * Set the secret key.
     *
     * @param string|null $secretKey
     * @return self
     */
    public function setSecretKey(?string $secretKey): self;

    /**
     * Get the secret key length.
     *
     * @return int|null
     */
    public function getSecretKeyLength(): ?int;

    /**
     * Set the secret key length.
     *
     * @param int|null $secretKeyLength
     * @return self
     */
    public function setSecretKeyLength(?int $secretKeyLength): self;

    /**
     * Get live indexing status.
     *
     * @return int|null
     */
    public function getEnableLiveIndexing(): ?int;

    /**
     * Set live indexing status.
     *
     * @param int|null $value
     * @return self
     */
    public function setEnableLiveIndexing(?int $value): self;

    /**
     * Get the entity sync cron expression.
     *
     * @return string|null
     */
    public function getEntitySyncCronExpr(): ?string;

    /**
     * Set the entity sync cron expression.
     *
     * @param string|null $value
     * @return self
     */
    public function setEntitySyncCronExpr(?string $value): self;

    /**
     * Get the per-minute rate.
     *
     * @return int|null
     */
    public function getPerMinute(): ?int;

    /**
     * Set the per-minute rate.
     *
     * @param int|null $value
     * @return self
     */
    public function setPerMinute(?int $value): self;

    /**
     * Get the chunk size.
     *
     * @return int|null
     */
    public function getChunkSize(): ?int;

    /**
     * Set the chunk size.
     *
     * @param int|null $value
     * @return self
     */
    public function setChunkSize(?int $value): self;

    /**
     * Get the task payload.
     *
     * @return array|null
     */
    public function getTaskPayload(): ?array;

    /**
     * Set the task payload.
     *
     * @param array|null $payload
     * @return self
     */
    public function setTaskPayload(?array $payload): self;
}
