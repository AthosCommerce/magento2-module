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

namespace AthosCommerce\Feed\Helper;

class Constants
{
    const PRODUCT_KEY = '__PRODUCT';

    const API_SCOPE_DELETE = 'products/delete';
    const API_SCOPE_UPSERT = 'products/update';

    /**
     * Setted default limit to avoid rate limit for store.
     */
    const DEFAULT_MAX_BATCH_LIMIT = 480;

    const XML_PATH_CONFIG_SITE_ID = 'athoscommerce/configuration/siteid';
    const XML_PATH_CONFIG_ENDPOINT = 'athoscommerce/configuration/endpoint';
    const XML_PATH_CONFIG_SECRET_KEY = 'athoscommerce/configuration/secretkey';
    const XML_PATH_CONFIG_SHOP_DOMAIN = 'athoscommerce/configuration/shopdomain';

    const XML_PATH_LIVE_INDEXING_ENABLED = 'athoscommerce/indexing/enable_live_indexing';
    const XML_PATH_LIVE_INDEXING_TASK_PAYLOAD = 'athoscommerce/indexing/task_payload';
    const XML_PATH_LIVE_INDEXING_PER_MINUTE = 'athoscommerce/indexing/request_per_minute';
}
