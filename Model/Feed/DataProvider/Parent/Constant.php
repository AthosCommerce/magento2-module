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

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Parent;

class Constant
{
    public const CATALOG_PRODUCT_ENTITY_ALIAS = 'e';
    public const CATALOG_PRODUCT_SUPER_LINK_ALIAS = 'l';
    public const CATALOG_PRODUCT_LINK = 'cpl';
    public const PARENT_CATALOG_PRODUCT_ENTITY_ALIAS = 'parent';

    public const IS_BELONG_TO_PARENT_KEY = '__is_belong_to_parent';
    public const IS_STANDALONE_PRODUCT_KEY = '___standalone_product';

    public const CONFIGURABLE_TYPE = 'configurable';
    public const GROUPED_TYPE = 'grouped';

    public const PARENT_ID = '__parent_id';
    public const PARENT_SKU = '__parent_sku';
    public const PARENT_TITLE = '__parent_title';
    public const PARENT_IMAGE = '__parent_image';

    public const PARENT_STATUS = 'parent_status';
    public const PARENT_URL = 'parent_url';
    public const PARENT_TYPE = 'parent_type_id';
    public const PARENT_VISIBILITY = 'parent_visibility';

    /**
     * The following constants are used to store resolved parent information in the product data array.
     * They are prefixed with '__' to avoid conflicts with existing product attributes.
     */
    public const RESOLVED_PARENT_ID_KEY = '__resolved_parent_id';
    public const RESOLVED_PARENT_SKU_KEY = '__resolved_parent_sku';
    public const RESOLVED_PARENT_TYPE_KEY = '__resolved_parent_type_id';
    public const RESOLVED_PARENT_ROW_SOURCE_KEY = '__resolved_parent_row_source';
}
