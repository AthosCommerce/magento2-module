<?php

namespace AthosCommerce\Feed\Model\Data;

use AthosCommerce\Feed\Api\Data\ConfigItemInterface;
use Magento\Framework\Api\AbstractExtensibleObject;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractExtensibleModel;

class ConfigItem extends AbstractExtensibleModel implements ConfigItemInterface
{
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
     * @return string|null
     */
    public function getShopDomain(): ?string
    {
        return $this->getData(self::SHOP_DOMAIN);
    }

    /**
     * @param string|null $shopDomain
     */
    public function setShopDomain(?string $shopDomain): self
    {
        return $this->setData(self::SHOP_DOMAIN, $shopDomain);
    }

    /**
     * @return int|null
     */
    public function getFeedId(): ?int
    {
        return $this->getData(self::FEED_ID);
    }

    /**
     * @param int|null $feedId
     */
    public function setFeedId(?int $feedId): self
    {
        return $this->setData(self::FEED_ID, $feedId);
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
            'siteId' => $this->getSiteId(),
            'secretKey' => $this->getSecretKey(),
            'endPoint' => $this->getEndPoint(),
            'shopDomain' => $this->getShopDomain(),
            'feedId' => $this->getFeedId(),
            'enableLiveIndexing' => $this->getEnableLiveIndexing(),
            'entitySyncCronExpr' => $this->getEntitySyncCronExpr(),
            'perMinute' => $this->getPerMinute(),
            'chunkSize' => $this->getChunkSize(),
        ];
    }
}
