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

namespace AthosCommerce\Feed\Model\Feed\Specification;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\AbstractExtensibleObject;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Api\Data\MediaGallerySpecificationInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationExtensionInterface;

class Feed extends AbstractExtensibleObject implements FeedSpecificationInterface
{

    /**
     * @return string|null
     */
    public function getStoreCode(): ?string
    {
        return $this->_get(self::STORE_CODE);
    }

    /**
     * @param string $code
     * @return FeedSpecificationInterface
     */
    public function setStoreCode(string $code): FeedSpecificationInterface
    {
        return $this->setData(self::STORE_CODE, $code);
    }

    /**
     * @return string|null
     */
    public function getHierarchySeparator(): ?string
    {
        return $this->_get(self::HIERARCHY_SEPARATOR);
    }

    /**
     * @param string $separator
     * @return FeedSpecificationInterface
     */
    public function setHierarchySeparator(string $separator): FeedSpecificationInterface
    {
        return $this->setData(self::HIERARCHY_SEPARATOR, $separator);
    }

    /**
     * @return string|null
     */
    public function getMultiValuedSeparator(): ?string
    {
        return $this->_get(self::MULTI_VALUED_SEPARATOR);
    }

    /**
     * @param string $separator
     * @return FeedSpecificationInterface
     */
    public function setMultiValuedSeparator(string $separator): FeedSpecificationInterface
    {
        return $this->setData(self::MULTI_VALUED_SEPARATOR, $separator);
    }

    /**
     * @return bool|null
     */
    public function getIncludeUrlHierarchy(): ?bool
    {
        return !is_null($this->_get(self::INCLUDE_URL_HIERARCHY))
            ? (bool)$this->_get(self::INCLUDE_URL_HIERARCHY)
            : null;
    }

    /**
     * @param bool $flag
     * @return FeedSpecificationInterface
     */
    public function setIncludeUrlHierarchy(bool $flag): FeedSpecificationInterface
    {
        return $this->setData(self::INCLUDE_URL_HIERARCHY, $flag);
    }

    /**
     * @return bool|null
     */
    public function getIncludeMenuCategories(): ?bool
    {
        return !is_null($this->_get(self::INCLUDE_MENU_CATEGORIES))
            ? (bool)$this->_get(self::INCLUDE_MENU_CATEGORIES)
            : null;
    }

    /**
     * @param bool $flag
     * @return FeedSpecificationInterface
     */
    public function setIncludeMenuCategories(bool $flag): FeedSpecificationInterface
    {
        return $this->setData(self::INCLUDE_MENU_CATEGORIES, $flag);
    }

    /**
     * @return bool|null
     */
    public function getIncludeJSONConfig(): ?bool
    {
        return !is_null($this->_get(self::INCLUDE_JSON_CONFIG))
            ? (bool)$this->_get(self::INCLUDE_JSON_CONFIG)
            : null;
    }

    /**
     * @param bool $flag
     * @return FeedSpecificationInterface
     */
    public function setIncludeJSONConfig(bool $flag): FeedSpecificationInterface
    {
        return $this->setData(self::INCLUDE_JSON_CONFIG, $flag);
    }

    /**
     * @return bool|null
     */
    public function getIncludeChildPrices(): ?bool
    {
        return !is_null($this->_get(self::INCLUDE_CHILD_PRICES))
            ? (bool)$this->_get(self::INCLUDE_CHILD_PRICES)
            : null;
    }

    /**
     * @param bool $flag
     * @return FeedSpecificationInterface
     */
    public function setIncludeChildPrices(bool $flag): FeedSpecificationInterface
    {
        return $this->setData(self::INCLUDE_CHILD_PRICES, $flag);
    }

    /**
     * @return bool|null
     */
    public function getIncludeTierPricing(): ?bool
    {
        return !is_null($this->_get(self::INCLUDE_TIER_PRICES))
            ? (bool)$this->_get(self::INCLUDE_TIER_PRICES)
            : null;
    }

    /**
     * @param bool $flag
     * @return FeedSpecificationInterface
     */
    public function setIncludeTierPricing(bool $flag): FeedSpecificationInterface
    {
        return $this->setData(self::INCLUDE_TIER_PRICES, $flag);
    }

    /**
     * @return int|null
     */
    public function getCustomerId(): ?int
    {
        return !is_null($this->_get(self::CUSTOMER_ID))
            ? (int)$this->_get(self::CUSTOMER_ID)
            : null;
    }

    /**
     * @param int $id
     * @return FeedSpecificationInterface
     */
    public function setCustomerId(int $id): FeedSpecificationInterface
    {
        return $this->setData(self::CUSTOMER_ID, $id);
    }

    /**
     * @return CustomerInterface|null
     */
    public function getCustomer(): ?CustomerInterface
    {
        return $this->_get(self::CUSTOMER);
    }

    /**
     * @param CustomerInterface $customer
     * @return FeedSpecificationInterface
     */
    public function setCustomer(CustomerInterface $customer): FeedSpecificationInterface
    {
        return $this->setData(self::CUSTOMER, $customer);
    }

    /**
     * @return array
     */
    public function getChildFields(): array
    {
        return $this->_get(self::CHILD_FIELDS) ?? [];
    }

    /**
     * @param array $fields
     * @return FeedSpecificationInterface
     */
    public function setChildFields(array $fields): FeedSpecificationInterface
    {
        return $this->setData(self::CHILD_FIELDS, $fields);
    }

    /**
     * @return bool|null
     */
    public function getIncludeOutOfStock(): ?bool
    {
        return !is_null($this->_get(self::INCLUDE_OUT_OF_STOCK))
            ? (bool)$this->_get(self::INCLUDE_OUT_OF_STOCK)
            : null;
    }

    /**
     * @param bool $flag
     * @return FeedSpecificationInterface
     */
    public function setIncludeOutOfStock(bool $flag): FeedSpecificationInterface
    {
        return $this->setData(self::INCLUDE_OUT_OF_STOCK, $flag);
    }

    /**
     * @return array
     */
    public function getIgnoreFields(): array
    {
        return $this->_get(self::IGNORE_FIELDS) ?? [];
    }

    /**
     * @param array $fields
     * @return FeedSpecificationInterface
     */
    public function setIgnoreFields(array $fields): FeedSpecificationInterface
    {
        return $this->setData(self::IGNORE_FIELDS, $fields);
    }

    /**
     * @return string|null
     */
    public function getFormat(): ?string
    {
        return $this->_get(self::FORMAT);
    }

    /**
     * @param string $format
     * @return FeedSpecificationInterface
     */
    public function setFormat(string $format): FeedSpecificationInterface
    {
        return $this->setData(self::FORMAT, $format);
    }

    /**
     * @return MediaGallerySpecificationInterface|null
     */
    public function getMediaGallerySpecification(): ?MediaGallerySpecificationInterface
    {
        return $this->_get(self::MEDIA_GALLERY_SPECIFICATION);
    }

    /**
     * @param MediaGallerySpecificationInterface $specification
     * @return FeedSpecificationInterface
     */
    public function setMediaGallerySpecification(MediaGallerySpecificationInterface $specification): FeedSpecificationInterface
    {
        return $this->setData(self::MEDIA_GALLERY_SPECIFICATION, $specification);
    }

    /**
     * @return FeedSpecificationExtensionInterface|null
     */
    public function getExtensionAttributes(): ?FeedSpecificationExtensionInterface
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @param FeedSpecificationExtensionInterface $extensionAttributes
     * @return FeedSpecificationInterface
     */
    public function setExtensionAttributes(FeedSpecificationExtensionInterface $extensionAttributes): FeedSpecificationInterface
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * @return string|null
     */
    public function getPreSignedUrl(): ?string
    {
        return $this->_get(self::PRE_SIGNED_URL);
    }

    /**
     * @param string $url
     * @return FeedSpecificationInterface
     */
    public function setPreSignedUrl(string $url): FeedSpecificationInterface
    {
        return $this->setData(self::PRE_SIGNED_URL, $url);
    }

    /**
     * @return string|null
     */
    public function getCatalogPreSignedUrl(): ?string
    {
        return $this->_get(self::CATALOG_PRE_SIGNED_URL);
    }

    /**
     * @param string $url
     * @return FeedSpecificationInterface
     */
    public function setCatalogPreSignedUrl(string $url): FeedSpecificationInterface
    {
        return $this->setData(self::CATALOG_PRE_SIGNED_URL, $url);
    }

    /**
     * @return bool
     */
    public function getIsMsiEnabled(): bool
    {
        return (bool)$this->_get(self::MSI_STATUS);
    }

    /**
     * @param bool $value
     * @return FeedSpecificationInterface
     */
    public function setIsMsiEnabled(bool $value): FeedSpecificationInterface
    {
        return $this->setData(self::MSI_STATUS, $value);
    }


    /**
     * @return array
     */
    public function getSwatchOptionFieldsNames(): array
    {
        return $this->_get(self::SETTING_NAME_SWATCH_OPTION_FIELD_NAMES) ?? [];
    }

    /**
     * @param array $fields
     * @return FeedSpecificationInterface
     */
    public function setSwatchOptionFieldsNames(array $fields): FeedSpecificationInterface
    {
        return $this->setData(self::SETTING_NAME_SWATCH_OPTION_FIELD_NAMES, $fields);
    }

    /**
     * @return array
     */
    public function getVariantAdditionalFields(): array
    {
        return $this->_get(self::VARIANT_ADDITIONAL_FIELDS) ?? [];
    }

    /**
     * @param array $fields
     * @return FeedSpecificationInterface
     */
    public function setVariantAdditionalFields(array $fields): FeedSpecificationInterface
    {
        return $this->setData(self::VARIANT_ADDITIONAL_FIELDS, $fields);
    }

    /**
     * @return string
     */
    public function getIndexingMode(): string
    {
        return $this->_get(self::INDEXING_MODE_KEY) ?? self::BULK_MODE;
    }

    /**
     * @param string $value
     * @return FeedSpecificationInterface
     */
    public function setIndexingMode(string $value): FeedSpecificationInterface
    {
        return $this->setData(self::INDEXING_MODE_KEY, $value);
    }

    /**
     * @return array
     */
    public function getAdditionalIgnoreFieldsByMode(): array
    {
        if ($this->getIndexingMode() === self::LIVE_MODE) {
            return self::LIVE_INDEXING_IGNORE_DATA_PROVIDERS;
        }
        return self::BULK_INDEXING_IGNORE_DATA_PROVIDERS;
    }

    /**
     * @return array
     */
    public function getExcludedProductIds(): array
    {
        return $this->_get(self::EXCLUDE_PRODUCT_IDS) ?? [];
    }

    /**
     * @param array $productIds
     * @return FeedSpecificationInterface
     */
    public function setExcludedProductIds(array $productIds): FeedSpecificationInterface
    {
        return $this->setData(self::EXCLUDE_PRODUCT_IDS, $productIds);
    }

    /**
     * @return bool
     */
    public function getIncludeAllVariants(): bool
    {
        return (bool)$this->_get(self::INCLUDE_ALL_VARIANTS);
    }

    /**
     * @param bool $value
     * @return FeedSpecificationInterface
     */
    public function setIncludeAllVariants(bool $value): FeedSpecificationInterface
    {
        return $this->setData(self::INCLUDE_ALL_VARIANTS, $value);
    }

    /**
     * @return string|null
     */
    public function getParentIdSourceFieldName(): ?string
    {
        $value = $this->_get(self::PARENT_ID_SOURCE_FIELD);
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    /**
     * @param string $value
     * @return FeedSpecificationInterface
     */
    public function setParentIdSourceFieldName(string $value): FeedSpecificationInterface
    {
        return $this->setData(self::PARENT_ID_SOURCE_FIELD, $value);
    }

    /**
     * @return string|null
     */
    public function getGroupBySourceFieldName(): ?string
    {
        $value = $this->_get(self::GROUP_ID_SOURCE_FIELD);
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    /**
     * @param string $value
     * @return FeedSpecificationInterface
     */
    public function setGroupBySourceFieldName(string $value): FeedSpecificationInterface
    {
        return $this->setData(self::GROUP_ID_SOURCE_FIELD, $value);
    }

    /**
     * @return int|null
     */
    public function getVariantAdditionalDataLimit(): ?int
    {
        $value = $this->_get(self::VARIANT_ADDITIONAL_DATA_LIMIT);
        if ($value === null || $value === '') {
            return 200;
        }

        $value = (int)$value;
        if ($value <= 0) {
            return 200;
        }

        return $value;
    }

    /**
     * @param int $limit
     * @return FeedSpecificationInterface
     */
    public function setVariantAdditionalDataLimit(int $limit): FeedSpecificationInterface
    {
        return $this->setData(self::VARIANT_ADDITIONAL_DATA_LIMIT, $limit);
    }

    /**
     * @return array
     */
    public function getIgnoreModifiers(): array
    {
        return $this->_get(self::IGNORE_MODIFIERS) ?? [];
    }

    /**
     * @param array $modifiers
     * @return FeedSpecificationInterface
     */
    public function setIgnoreModifiers(array $modifiers): FeedSpecificationInterface
    {
        return $this->setData(self::IGNORE_MODIFIERS, $modifiers);
    }

    /**
     * @return string
     */
    public function getEnableCriteriaFilter(): bool
    {
        return (bool)$this->_get(self::ENABLE_CRITERIA_FILTER);
    }

    /**
     * @param bool $value
     * @return FeedSpecificationInterface
     */
    public function setEnableCriteriaFilter(bool $value): FeedSpecificationInterface
    {
        return $this->setData(self::ENABLE_CRITERIA_FILTER, $value);
    }

    /**
     * @return string|null
     */
    public function getCriteriaField(): ?string
    {
        $value = trim((string)$this->_get(self::CRITERIA_FIELD));
        return $value !== '' ? $value : null;
    }

    /**
     * @param string $value
     * @return FeedSpecificationInterface
     */
    public function setCriteriaField(string $value): FeedSpecificationInterface
    {
        return $this->setData(self::CRITERIA_FIELD, $value);
    }

    /**
     * @return string|null
     */
    public function getCriteriaOperator(): ?string
    {
        $value = trim((string)$this->_get(self::CRITERIA_OPERATOR));
        return $value !== '' ? $value : null;
    }

    /**
     * @param string $value
     * @return FeedSpecificationInterface
     */
    public function setCriteriaOperator(string $value): FeedSpecificationInterface
    {
        return $this->setData(self::CRITERIA_OPERATOR, $value);
    }

    /**
     * Accepts string, int, or array (for in/nin operators). Returns null when not set.
     *
     * @return array|string|int|null
     */
    public function getCriteriaValue()
    {
        return $this->_get(self::CRITERIA_VALUE);
    }

    /**
     * @param array|string|int|null $value
     * @return FeedSpecificationInterface
     */
    public function setCriteriaValue($value): FeedSpecificationInterface
    {
        return $this->setData(self::CRITERIA_VALUE, $value);
    }

    /**
     * @return bool
     */
    public function getConfigTreatAsRow(): bool
    {
        return (bool)$this->_get(self::CONFIG_TREAT_AS_ROW);
    }

    /**
     * @param bool $value
     * @return FeedSpecificationInterface
     */
    public function setConfigTreatAsRow(bool $value): FeedSpecificationInterface
    {
        return $this->setData(self::CONFIG_TREAT_AS_ROW, $value);
    }

    /**
     * @return bool
     */
    public function getGroupedTreatAsRow(): bool
    {
        return (bool)$this->_get(self::GROUPED_TREAT_AS_ROW);
    }

    /**
     * @param bool $value
     * @return FeedSpecificationInterface
     */
    public function setGroupedTreatAsRow(bool $value): FeedSpecificationInterface
    {
        return $this->setData(self::GROUPED_TREAT_AS_ROW, $value);
    }

    /**
     * @return bool
     */
    public function getCustomStockEnabled(): bool
    {
        return (bool)$this->_get(self::CUSTOM_STOCK_ENABLED);
    }

    /**
     * @param bool $value
     * @return FeedSpecificationInterface
     */
    public function setCustomStockEnabled(bool $value): FeedSpecificationInterface
    {
        return $this->setData(self::CUSTOM_STOCK_ENABLED, $value);
    }

    /**
     * @return string|null
     */
    public function getCustomStockTable(): ?string
    {
        $value = trim((string)$this->_get(self::CUSTOM_STOCK_TABLE));
        return $value !== '' ? $value : null;
    }

    /**
     * @param string $value
     * @return FeedSpecificationInterface
     */
    public function setCustomStockTable(string $value): FeedSpecificationInterface
    {
        return $this->setData(self::CUSTOM_STOCK_TABLE, $value);
    }

    /**
     * @return string|null
     */
    public function getCustomStockJoinKey(): ?string
    {
        $value = trim((string)$this->_get(self::CUSTOM_STOCK_JOIN_KEY));
        return $value !== '' ? $value : null;
    }

    /**
     * @param string $value
     * @return FeedSpecificationInterface
     */
    public function setCustomStockJoinKey(string $value): FeedSpecificationInterface
    {
        return $this->setData(self::CUSTOM_STOCK_JOIN_KEY, $value);
    }

    /**
     * @return string|null
     */
    public function getCustomStockIdentifierField(): ?string
    {
        $value = trim((string)$this->_get(self::CUSTOM_STOCK_IDENTIFIER_FIELD));
        return $value !== '' ? $value : null;
    }

    /**
     * @param string $value
     * @return FeedSpecificationInterface
     */
    public function setCustomStockIdentifierField(string $value): FeedSpecificationInterface
    {
        return $this->setData(self::CUSTOM_STOCK_IDENTIFIER_FIELD, $value);
    }

    /**
     * @return string|null
     */
    public function getCustomStockInStockField(): ?string
    {
        $value = trim((string)$this->_get(self::CUSTOM_STOCK_IN_STOCK_FIELD));
        return $value !== '' ? $value : null;
    }

    /**
     * @param string $value
     * @return FeedSpecificationInterface
     */
    public function setCustomStockInStockField(string $value): FeedSpecificationInterface
    {
        return $this->setData(self::CUSTOM_STOCK_IN_STOCK_FIELD, $value);
    }

    /**
     * @return string|null
     */
    public function getCustomStockQtyField(): ?string
    {
        $value = trim((string)$this->_get(self::CUSTOM_STOCK_QTY_FIELD));
        return $value !== '' ? $value : null;
    }

    /**
     * @param string $value
     * @return FeedSpecificationInterface
     */
    public function setCustomStockQtyField(string $value): FeedSpecificationInterface
    {
        return $this->setData(self::CUSTOM_STOCK_QTY_FIELD, $value);
    }
}