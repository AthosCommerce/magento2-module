<?php

namespace AthosCommerce\Feed\Model\Data;

use AthosCommerce\Feed\Api\Data\ConfigItemInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Api\Data\MediaGallerySpecificationExtensionInterface;
use AthosCommerce\Feed\Api\Data\MediaGallerySpecificationInterface;
use Magento\Framework\Api\AbstractExtensibleObject;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractExtensibleModel;

class ConfigItem extends AbstractExtensibleObject implements ConfigItemInterface
{
    /**
     * @return string
     */
    public function getStoreCode(): string
    {
        return (string)$this->_get(self::STORE_CODE);
    }

    /**
     * @param string $storeCode
     * @return ConfigItemInterface
     */
    public function setStoreCode(string $storeCode): ConfigItemInterface
    {
        return $this->setData(self::STORE_CODE, $storeCode);
    }

    /**
     * @return string|null
     */
    public function getSiteId(): ?string
    {
        return $this->_get(self::SITE_ID);
    }

    /**
     * @param string|null $siteId
     * @return ConfigItemInterface
     */
    public function setSiteId(?string $siteId): ConfigItemInterface
    {
        return $this->setData(self::SITE_ID, $siteId);
    }

    /**
     * @return string|null
     */
    public function getEndPoint(): ?string
    {
        return $this->_get(self::END_POINT);
    }

    /**
     * @param string|null $endPoint
     * @return ConfigItemInterface
     */
    public function setEndPoint(?string $endPoint): ConfigItemInterface
    {
        return $this->setData(self::END_POINT, $endPoint);
    }

    /**
     * @return string|null
     */
    public function getSecretKey(): ?string
    {
        return $this->_get(self::SECRET_KEY);
    }

    /**
     * @param string|null $secretKey
     * @return ConfigItemInterface
     */
    public function setSecretKey(?string $secretKey): ConfigItemInterface
    {
        return $this->setData(self::SECRET_KEY, $secretKey);
    }

    /**
     * @return int|null
     */
    public function getEnableLiveIndexing(): ?int
    {
        return $this->_get(self::ENABLE_LIVE_INDEXING);
    }

    /**
     * @param int|null $enableLiveIndexing
     * @return ConfigItemInterface
     */
    public function setEnableLiveIndexing(?int $enableLiveIndexing): ConfigItemInterface
    {
        return $this->setData(self::ENABLE_LIVE_INDEXING, $enableLiveIndexing);
    }

    /**
     * @return string|null
     */
    public function getEntitySyncCronExpr(): ?string
    {
        return $this->_get(self::ENTITY_SYNC_CRON_EXPR);
    }

    /**
     * @param string|null $entitySyncCronExpr
     * @return ConfigItemInterface
     */
    public function setEntitySyncCronExpr(?string $entitySyncCronExpr): ConfigItemInterface
    {
        return $this->setData(self::ENTITY_SYNC_CRON_EXPR, $entitySyncCronExpr);
    }

    /**
     * @return int|null
     */
    public function getPerMinute(): ?int
    {
        return $this->_get(self::PER_MINUTE);
    }

    /**
     * @param int|null $perMinute
     * @return ConfigItemInterface
     */
    public function setPerMinute(?int $perMinute): ConfigItemInterface
    {
        return $this->setData(self::PER_MINUTE, $perMinute);
    }

    /**
     * @return int|null
     */
    public function getChunkSize(): ?int
    {
        return $this->_get(self::CHUNK_SIZE);
    }

    /**
     * @param int|null $chunkSize
     * @return ConfigItemInterface
     */
    public function setChunkSize(?int $chunkSize): ConfigItemInterface
    {
        return $this->setData(self::CHUNK_SIZE, $chunkSize);
    }

    /**
     * @return string|null
     */
    public function getHierarchySeparator(): ?string
    {
        return $this->_get(FeedSpecificationInterface::HIERARCHY_SEPARATOR);
    }

    /**
     * @param string|null $hierarchySeparator
     * @return ConfigItemInterface
     */
    public function setHierarchySeparator(?string $hierarchySeparator): ConfigItemInterface
    {
        return $this->setData(
            FeedSpecificationInterface::HIERARCHY_SEPARATOR,
            $hierarchySeparator
        );
    }

    public function getMultiValuedSeparator(): ?string
    {
        return $this->_get(FeedSpecificationInterface::MULTI_VALUED_SEPARATOR);
    }

    /**
     * @param string $separator
     * @return ConfigItemInterface
     */
    public function setMultiValuedSeparator(string $separator): ConfigItemInterface
    {
        return $this->setData(FeedSpecificationInterface::MULTI_VALUED_SEPARATOR, $separator);
    }

    /**
     * @return bool|null
     */
    public function getIncludeUrlHierarchy(): ?bool
    {
        return !is_null($this->_get(FeedSpecificationInterface::INCLUDE_URL_HIERARCHY))
            ? (bool)$this->_get(FeedSpecificationInterface::INCLUDE_URL_HIERARCHY)
            : null;
    }

    /**
     * @param bool $flag
     * @return ConfigItemInterface
     */
    public function setIncludeUrlHierarchy(bool $flag): ConfigItemInterface
    {
        return $this->setData(FeedSpecificationInterface::INCLUDE_URL_HIERARCHY, $flag);
    }

    /**
     * @return bool|null
     */
    public function getIncludeMenuCategories(): ?bool
    {
        return !is_null($this->_get(FeedSpecificationInterface::INCLUDE_MENU_CATEGORIES))
            ? (bool)$this->_get(FeedSpecificationInterface::INCLUDE_MENU_CATEGORIES)
            : null;
    }

    /**
     * @param bool $flag
     * @return ConfigItemInterface
     */
    public function setIncludeMenuCategories(bool $flag): ConfigItemInterface
    {
        return $this->setData(FeedSpecificationInterface::INCLUDE_MENU_CATEGORIES, $flag);
    }

    /**
     * @return bool|null
     */
    public function getIncludeJSONConfig(): ?bool
    {
        return !is_null($this->_get(FeedSpecificationInterface::INCLUDE_JSON_CONFIG))
            ? (bool)$this->_get(FeedSpecificationInterface::INCLUDE_JSON_CONFIG)
            : null;
    }

    /**
     * @param bool $flag
     * @return ConfigItemInterface
     */
    public function setIncludeJSONConfig(bool $flag): ConfigItemInterface
    {
        return $this->setData(FeedSpecificationInterface::INCLUDE_JSON_CONFIG, $flag);
    }

    /**
     * @return bool|null
     */
    public function getIncludeChildPrices(): ?bool
    {
        return !is_null($this->_get(FeedSpecificationInterface::INCLUDE_CHILD_PRICES))
            ? (bool)$this->_get(FeedSpecificationInterface::INCLUDE_CHILD_PRICES)
            : null;
    }

    /**
     * @param bool $flag
     * @return ConfigItemInterface
     */
    public function setIncludeChildPrices(bool $flag): ConfigItemInterface
    {
        return $this->setData(FeedSpecificationInterface::INCLUDE_CHILD_PRICES, $flag);
    }

    /**
     * @return bool|null
     */
    public function getIncludeTierPricing(): ?bool
    {
        return !is_null($this->_get(FeedSpecificationInterface::INCLUDE_TIER_PRICES))
            ? (bool)$this->_get(FeedSpecificationInterface::INCLUDE_TIER_PRICES)
            : null;
    }

    /**
     * @param bool $flag
     * @return ConfigItemInterface
     */
    public function setIncludeTierPricing(bool $flag): ConfigItemInterface
    {
        return $this->setData(FeedSpecificationInterface::INCLUDE_TIER_PRICES, $flag);
    }

    /**
     * @return bool|null
     */
    public function getIncludeOutOfStock(): ?bool
    {
        return !is_null($this->_get(FeedSpecificationInterface::INCLUDE_OUT_OF_STOCK))
            ? (bool)$this->_get(FeedSpecificationInterface::INCLUDE_OUT_OF_STOCK)
            : null;
    }

    /**
     * @param bool $flag
     * @return ConfigItemInterface
     */
    public function setIncludeOutOfStock(bool $flag): ConfigItemInterface
    {
        return $this->setData(FeedSpecificationInterface::INCLUDE_OUT_OF_STOCK, $flag);
    }

    /**
     * @return string[]
     */
    public function getIgnoreFields()
    {
        return $this->_get(FeedSpecificationInterface::IGNORE_FIELDS) ?? [];
    }

    /**
     * @param $fields
     * @return ConfigItemInterface
     */
    public function setIgnoreFields($fields): ConfigItemInterface
    {
        return $this->setData(FeedSpecificationInterface::IGNORE_FIELDS, $fields);
    }

    /**
     * @return bool
     */
    public function getMsiStatus(): bool
    {
        return $this->_get(FeedSpecificationInterface::MSI_STATUS);
    }

    /**
     * @param bool $value
     * @return ConfigItemInterface
     */
    public function setMsiStatus(bool $value): ConfigItemInterface
    {
        return $this->setData(FeedSpecificationInterface::MSI_STATUS, $value);
    }


    /**
     * @return string[]
     */
    public function getSwatchOptionFieldsNames()
    {
        return $this->_get(FeedSpecificationInterface::SETTING_NAME_SWATCH_OPTION_FIELD_NAMES) ?? [];
    }

    /**
     * @param $fields
     * @return ConfigItemInterface
     */
    public function setSwatchOptionFieldsNames($fields): ConfigItemInterface
    {
        return $this->setData(FeedSpecificationInterface::SETTING_NAME_SWATCH_OPTION_FIELD_NAMES, $fields);
    }

    /**
     * @return string[]
     */
    public function getVariantAdditionalFields()
    {
        return $this->_get(FeedSpecificationInterface::SETTING_NAME_VARIANT_ADDITIONAL_FIELDS) ?? [];
    }

    /**
     * @param $fields
     * @return ConfigItemInterface
     */
    public function setVariantAdditionalFields($fields): ConfigItemInterface
    {
        return $this->setData(FeedSpecificationInterface::SETTING_NAME_VARIANT_ADDITIONAL_FIELDS, $fields);
    }

    /**
     * @return string[]
     */
    public function getExcludedProductIds()
    {
        return $this->_get(FeedSpecificationInterface::EXCLUDE_PRODUCT_IDS) ?? [];
    }

    /**
     * @param $productIds
     * @return ConfigItemInterface
     */
    public function setExcludedProductIds($productIds): ConfigItemInterface
    {
        return $this->setData(FeedSpecificationInterface::EXCLUDE_PRODUCT_IDS, $productIds);
    }

    /**
     * @return int
     */
    public function getThumbWidth(): ?int
    {
        return !is_null($this->_get(MediaGallerySpecificationInterface::THUMB_WIDTH))
            ? (int)$this->_get(MediaGallerySpecificationInterface::THUMB_WIDTH)
            : null;
    }

    /**
     * @param int|null $width
     * @return ConfigItemInterface
     */
    public function setThumbWidth(?int $width): ConfigItemInterface
    {
        return $this->setData(MediaGallerySpecificationInterface::THUMB_WIDTH, $width);
    }

    /**
     * @return int|null
     */
    public function getThumbHeight(): ?int
    {
        return !is_null($this->_get(MediaGallerySpecificationInterface::THUMB_HEIGHT))
            ? (int)$this->_get(MediaGallerySpecificationInterface::THUMB_HEIGHT)
            : null;
    }

    /**
     * @param int|null $height
     * @return ConfigItemInterface
     */
    public function setThumbHeight(?int $height): ConfigItemInterface
    {
        return $this->setData(MediaGallerySpecificationInterface::THUMB_HEIGHT, $height);
    }

    /**
     * @return bool|null
     */
    public function getKeepAspectRatio(): ?bool
    {
        return !is_null($this->_get(MediaGallerySpecificationInterface::KEEP_ASPECT_RATIO))
            ? (bool)$this->_get(MediaGallerySpecificationInterface::KEEP_ASPECT_RATIO)
            : null;
    }

    /**
     * @param bool|null $flag
     * @return ConfigItemInterface
     */
    public function setKeepAspectRatio(?bool $flag): ConfigItemInterface
    {
        return $this->setData(MediaGallerySpecificationInterface::KEEP_ASPECT_RATIO, $flag);
    }

    /**
     * @return string[]
     */
    public function getImageTypes()
    {
        return $this->_get(MediaGallerySpecificationInterface::IMAGE_TYPES) ?? [];
    }

    /**
     * @param $types
     * @return ConfigItemInterface
     */
    public function setImageTypes($types): ConfigItemInterface
    {
        return $this->setData(MediaGallerySpecificationInterface::IMAGE_TYPES, $types);
    }

    /**
     * @return bool
     */
    public function getIncludeMediaGallery(): ?bool
    {
        return !is_null($this->_get(MediaGallerySpecificationInterface::INCLUDE_MEDIA_GALLERY))
            ? (bool)$this->_get(MediaGallerySpecificationInterface::INCLUDE_MEDIA_GALLERY)
            : null;
    }

    /**
     * @param bool $flag
     * @return ConfigItemInterface
     */
    public function setIncludeMediaGallery(bool $flag): ConfigItemInterface
    {
        return $this->setData(MediaGallerySpecificationInterface::INCLUDE_MEDIA_GALLERY, $flag);
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
            'enableLiveIndexing' => $this->getEnableLiveIndexing(),
            'entitySyncCronExpr' => $this->getEntitySyncCronExpr(),
            'perMinute' => $this->getPerMinute(),
            'chunkSize' => $this->getChunkSize(),
            // Media Gallery Specs
            'thumbWidth' => $this->getThumbWidth(),
            'thumbHeight' => $this->getThumbHeight(),
            'keepAspectRatio' => $this->getKeepAspectRatio(),
            'imageTypes' => $this->getImageTypes(),
            'includeMediaGallery' => $this->getIncludeMediaGallery(),
            // Feed Specs
            'multiValuedSeparator' => $this->getMultiValuedSeparator(),
            'includeChildPrices' => $this->getIncludeChildPrices(),
            'includeJSONConfig' => $this->getIncludeJSONConfig(),
            'hierarchySeparator' => $this->getHierarchySeparator(),
            'includeUrlHierarchy' => $this->getIncludeUrlHierarchy(),
            'includeTierPricing' => $this->getIncludeTierPricing(),
            'includeOutOfStock' => $this->getIncludeOutOfStock(),
            'includeMenuCategories' => $this->getIncludeMenuCategories(),
            'ignoreFields' => $this->getIgnoreFields(),
            'swatchOptionFieldsNames' => $this->getSwatchOptionFieldsNames(),
            'variantAdditionalFields' => $this->getVariantAdditionalFields(),
            'excludedProductIds' => $this->getExcludedProductIds(),
        ];
    }
}
