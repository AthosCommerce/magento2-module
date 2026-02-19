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

    public const ENABLE_LIVE_INDEXING = 'enableLiveIndexing';
    public const ENTITY_SYNC_CRON_EXPR = 'entitySyncCronExpr';
    public const PER_MINUTE = 'perMinute';
    public const CHUNK_SIZE = 'chunkSize';

    public const ENABLE_DEBUG_LOG = 'enableDebugLog';

    /**
     * @return string
     */
    public function getStoreCode(): string;

    /**
     * @param string $storeCode
     * @return ConfigItemInterface
     */
    public function setStoreCode(string $storeCode): ConfigItemInterface;

    /**
     * @return string|null
     */
    public function getSiteId(): ?string;

    /**
     * @param string|null $siteId
     * @return ConfigItemInterface
     */
    public function setSiteId(?string $siteId): ConfigItemInterface;

    /**
     * @return string|null
     */
    public function getEndPoint(): ?string;

    /**
     * @param string|null $value
     * @return ConfigItemInterface
     */
    public function setEndPoint(?string $value): ConfigItemInterface;

    /**
     * @return string|null
     */
    public function getSecretKey(): ?string;

    /**
     * @param string|null $secretKey
     * @return ConfigItemInterface
     */
    public function setSecretKey(?string $secretKey): ConfigItemInterface;

    /**
     * @return int|null
     */
    public function getEnableLiveIndexing(): ?int;

    /**
     * @param int|null $value
     * @return ConfigItemInterface
     */
    public function setEnableLiveIndexing(?int $value): ConfigItemInterface;

    /**
     * @return string|null
     */
    public function getEntitySyncCronExpr(): ?string;

    /**
     * @param string|null $value
     * @return ConfigItemInterface
     */
    public function setEntitySyncCronExpr(?string $value): ConfigItemInterface;

    /**
     * @return int|null
     */
    public function getPerMinute(): ?int;

    /**
     * @param int|null $value
     * @return ConfigItemInterface
     */
    public function setPerMinute(?int $value): ConfigItemInterface;

    /**
     * @return int|null
     */
    public function getChunkSize(): ?int;

    /**
     * @param int|null $value
     * @return ConfigItemInterface
     */
    public function setChunkSize(?int $value): ConfigItemInterface;

    /**
     * @return string|null
     */
    public function getHierarchySeparator(): ?string;

    /**
     * @param string $separator
     * @return ConfigItemInterface
     */
    public function setHierarchySeparator(string $separator): ConfigItemInterface;

    /**
     * @return string|null
     */
    public function getMultiValuedSeparator(): ?string;

    /**
     * @param string $separator
     * @return ConfigItemInterface
     */
    public function setMultiValuedSeparator(string $separator): ConfigItemInterface;

    /**
     * @return bool|null
     */
    public function getIncludeUrlHierarchy(): ?bool;

    /**
     * @param bool $flag
     * @return ConfigItemInterface
     */
    public function setIncludeUrlHierarchy(bool $flag): ConfigItemInterface;

    /**
     * @return bool|null
     */
    public function getIncludeMenuCategories(): ?bool;

    /**
     * @param bool $flag
     * @return ConfigItemInterface
     */
    public function setIncludeMenuCategories(bool $flag): ConfigItemInterface;

    /**
     * @return bool|null
     */
    public function getIncludeJSONConfig(): ?bool;

    /**
     * @param bool $flag
     * @return ConfigItemInterface
     */
    public function setIncludeJSONConfig(bool $flag): ConfigItemInterface;

    /**
     * @return bool|null
     */
    public function getIncludeChildPrices(): ?bool;

    /**
     * @param bool $flag
     * @return ConfigItemInterface
     */
    public function setIncludeChildPrices(bool $flag): ConfigItemInterface;

    /**
     * @return bool|null
     */
    public function getIncludeTierPricing(): ?bool;

    /**
     * @param bool $flag
     * @return ConfigItemInterface
     */
    public function setIncludeTierPricing(bool $flag): ConfigItemInterface;

    /**
     * @return bool|null
     */
    public function getIncludeOutOfStock(): ?bool;

    /**
     * @param bool $flag
     * @return ConfigItemInterface
     */
    public function setIncludeOutOfStock(bool $flag): ConfigItemInterface;

    /**
     * @return string[]
     */
    public function getIgnoreFields();

    /**
     * @param $fields
     * @return ConfigItemInterface
     */
    public function setIgnoreFields($fields): ConfigItemInterface;

    /**
     * @return bool
     */
    public function getIsMsiEnabled(): ?bool;

    /**
     * @param bool|null $value
     * @return ConfigItemInterface
     */
    public function setIsMsiEnabled(?bool $value): ConfigItemInterface;

    /**
     * @return string[]
     */
    public function getSwatchOptionSourceFieldNames();

    /**
     * @param $fields
     * @return ConfigItemInterface
     */
    public function setSwatchOptionSourceFieldNames($fields): ConfigItemInterface;

    /**
     * @return string[]
     */
    public function getExcludedProductIds();

    /**
     * @param $productIds
     * @return ConfigItemInterface
     */
    public function setExcludedProductIds($productIds): ConfigItemInterface;

    /**
     * @return int|null
     */
    public function getThumbWidth(): ?int;

    /**
     * @param int|null $width
     * @return ConfigItemInterface
     */
    public function setThumbWidth(?int $width): ConfigItemInterface;

    /**
     * @return int|null
     */
    public function getThumbHeight(): ?int;

    /**
     * @param int|null $height
     * @return ConfigItemInterface
     */
    public function setThumbHeight(int $height): ConfigItemInterface;

    /**
     * @return bool
     */
    public function getKeepAspectRatio(): ?bool;

    /**
     * @param bool $flag
     * @return ConfigItemInterface
     */
    public function setKeepAspectRatio(?bool $flag): self;

    /**
     * @return string[]
     */
    public function getImageTypes();

    /**
     * @param $types
     * @return ConfigItemInterface
     */
    public function setImageTypes($types): ConfigItemInterface;

    /**
     * @return bool
     */
    public function getIncludeMediaGallery(): ?bool;

    /**
     * @param bool $flag
     * @return ConfigItemInterface
     */
    public function setIncludeMediaGallery(bool $flag): ConfigItemInterface;

    /**
     * @return bool|null
     */
    public function getEnableDebugLog(): ?bool;

    /**
     * @param bool|null $value
     * @return ConfigItemInterface
     */
    public function setEnableDebugLog(?bool $value): ConfigItemInterface;

    /**
     * @return bool
     */
    public function getIncludeAllVariants(): ?bool;

    /**
     * @param bool|null $value
     * @return ConfigItemInterface
     */
    public function setIncludeAllVariants(?bool $value): ConfigItemInterface;
}
