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

namespace AthosCommerce\Feed\Api\Data;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\ExtensibleDataInterface;

interface FeedSpecificationInterface extends ExtensibleDataInterface
{
    /**
     *
     */
    public const STORE_CODE = 'store_code';
    /**
     *
     */
    public const HIERARCHY_SEPARATOR = 'hierarchy_separator';
    /**
     *
     */
    public const MULTI_VALUED_SEPARATOR = 'multi_valued_separator';
    /**
     *
     */
    public const INCLUDE_URL_HIERARCHY = 'include_url_hierarchy';
    /**
     *
     */
    public const INCLUDE_MENU_CATEGORIES = 'include_menu_categories';
    /**
     *
     */
    public const INCLUDE_JSON_CONFIG = 'include_json_config';
    /**
     *
     */
    public const INCLUDE_CHILD_PRICES = 'include_child_prices';
    /**
     *
     */
    public const INCLUDE_TIER_PRICES = 'include_tier_prices';
    /**
     *
     */
    public const CUSTOMER_ID = 'customer_id';
    /**
     *
     */
    public const CUSTOMER = 'customer';
    /**
     *
     */
    public const CHILD_FIELDS = 'child_fields';
    /**
     *
     */
    public const INCLUDE_OUT_OF_STOCK = 'include_out_of_stock';
    /**
     *
     */
    public const IGNORE_FIELDS = 'ignore_fields';
    /**
     *
     */
    public const FORMAT = 'format';
    /**
     *
     */
    public const MEDIA_GALLERY_SPECIFICATION = 'media_gallery_specification';
    /**
     *
     */
    public const PRE_SIGNED_URL = 'presigned_url';
    /**
     *
     */
    public const MSI_STATUS = true;
    /**
     *
     */
    public const SETTING_NAME_SWATCH_OPTION_FIELD_NAMES = 'swatch_option_Fields';
    /**
     *
     */
    public const SETTING_NAME_VARIANT_ADDITIONAL_FIELDS = 'variant_additional_fields';

    public const INDEXING_MODE_KEY = 'indexing_mode';
    public const BULK_MODE = 'bulk';
    public const LIVE_MODE = 'live';

    //Define data providers to ignore for each indexing mode
    public const BULK_INDEXING_IGNORE_DATA_PROVIDERS = ['__group_by_swatch'];
    public const LIVE_INDEXING_IGNORE_DATA_PROVIDERS = ['__origin_timestamp'];

    public const EXCLUDE_PRODUCT_IDS = 'exclude_product_ids';

    /**
     *
     */
    public const INCLUDE_ALL_VARIANTS = false;
    /**
     * @return string|null
     */
    public function getStoreCode() : ?string;

    /**
     * @param string $code
     * @return FeedSpecificationInterface
     */
    public function setStoreCode(string $code) : self;
    /**
     * @return string|null
     */
    public function getHierarchySeparator() : ?string;

    /**
     * @param string $separator
     * @return FeedSpecificationInterface
     */
    public function setHierarchySeparator(string $separator) : self;
    /**
     * @return string|null
     */
    public function getMultiValuedSeparator() : ?string;

    /**
     * @param string $separator
     * @return FeedSpecificationInterface
     */
    public function setMultiValuedSeparator(string $separator) : self;

    /**
     * @return bool|null
     */
    public function getIncludeUrlHierarchy() : ?bool;

    /**
     * @param bool $flag
     * @return FeedSpecificationInterface
     */
    public function setIncludeUrlHierarchy(bool $flag) : self;

    /**
     * @return bool|null
     */
    public function getIncludeMenuCategories() : ?bool;

    /**
     * @param bool $flag
     * @return FeedSpecificationInterface
     */
    public function setIncludeMenuCategories(bool $flag) : self;

    /**
     * @return bool|null
     */
    public function getIncludeJSONConfig() : ?bool;

    /**
     * @param bool $flag
     * @return FeedSpecificationInterface
     */
    public function setIncludeJSONConfig(bool $flag) : self;

    /**
     * @return bool|null
     */
    public function getIncludeChildPrices() : ?bool;

    /**
     * @param bool $flag
     * @return FeedSpecificationInterface
     */
    public function setIncludeChildPrices(bool $flag) : self;

    /**
     * @return bool|null
     */
    public function getIncludeTierPricing() : ?bool;

    /**
     * @param bool $flag
     * @return FeedSpecificationInterface
     */
    public function setIncludeTierPricing(bool $flag) : self;

    /**
     * @return int|null
     */
    public function getCustomerId() : ?int;

    /**
     * @param int $id
     * @return FeedSpecificationInterface
     */
    public function setCustomerId(int $id) : self;

    /**
     * @return CustomerInterface|null
     */
    public function getCustomer() : ?CustomerInterface;

    /**
     * @param CustomerInterface $customer
     * @return FeedSpecificationInterface
     */
    public function setCustomer(CustomerInterface $customer) : self;

    /**
     * @return array
     */
    public function getChildFields() : array;

    /**
     * @param array $fields
     * @return FeedSpecificationInterface
     */
    public function setChildFields(array $fields) : self;

    /**
     * @return bool|null
     */
    public function getIncludeOutOfStock() : ?bool;

    /**
     * @param bool $flag
     * @return FeedSpecificationInterface
     */
    public function setIncludeOutOfStock(bool $flag) : self;

    /**
     * @return array
     */
    public function getIgnoreFields() : array;

    /**
     * @param array $fields
     * @return FeedSpecificationInterface
     */
    public function setIgnoreFields(array $fields) : self;

    /**
     * @return string|null
     */
    public function getFormat() : ?string;

    /**
     * @param string $format
     * @return FeedSpecificationInterface
     */
    public function setFormat(string $format) : self;

    /**
     * @return MediaGallerySpecificationInterface|null
     */
    public function getMediaGallerySpecification() : ?MediaGallerySpecificationInterface;

    /**
     * @param MediaGallerySpecificationInterface $specification
     * @return FeedSpecificationInterface
     */
    public function setMediaGallerySpecification(MediaGallerySpecificationInterface $specification) : self;

    /**
     * @return string|null
     */
    public function getPreSignedUrl() : ?string;

    /**
     * @param string $url
     * @return FeedSpecificationInterface
     */
    public function setPreSignedUrl(string $url) : self;

    /**
     * @return \AthosCommerce\Feed\Api\Data\FeedSpecificationExtensionInterface|null
     */
    public function getExtensionAttributes(): ?\AthosCommerce\Feed\Api\Data\FeedSpecificationExtensionInterface;

    /**
     * @param \AthosCommerce\Feed\Api\Data\FeedSpecificationExtensionInterface $extensionAttributes
     * @return FeedSpecificationInterface
     */
    public function setExtensionAttributes(
        \AthosCommerce\Feed\Api\Data\FeedSpecificationExtensionInterface $extensionAttributes
    ): self;

    /**
     * @return bool
     */
    public function getIsMsiEnabled() : bool;

    /**
     * @param bool $value
     * @return FeedSpecificationInterface
     */
    public function setIsMsiEnabled(bool $value) : self;

    /**
     * @return array
     */
    public function getSwatchOptionFieldsNames() : array;

    /**
     * @param array $fields
     * @return FeedSpecificationInterface
     */
    public function setSwatchOptionFieldsNames(array $fields) : self;

    /**
     * @return array
     */
    public function getVariantAdditionalFields() : array;

    /**
     * @param array $fields
     * @return FeedSpecificationInterface
     */
    public function setVariantAdditionalFields(array $fields) : self;
    /**
     * @return string|null
     */
    public function getIndexingMode() : ?string;

    /**
     * @param string $url
     * @return FeedSpecificationInterface
     */
    public function setIndexingMode(string $url) : self;

    /**
     * @return array
     */
    public function getAdditionalIgnoreFieldsByMode(): array;

    /**
     * @return array
     */
    public function getExcludedProductIds() : array;

    /**
     * @param array $productIds
     * @return FeedSpecificationInterface
     */
    public function setExcludedProductIds(array $productIds) : self;

    /**
     * @return bool
     */
    public function getIncludeAllVariants() : bool;

    /**
     * @param bool $value
     * @return FeedSpecificationInterface
     */
    public function setIncludeAllVariants(bool $value) : self;
}
