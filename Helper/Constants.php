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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Helper;

class Constants
{
    public const PRODUCT_KEY = '__PRODUCT';

    public const API_SCOPE_DELETE = 'product/delete';
    public const API_SCOPE_UPSERT = 'product/update';

    /**
     * Setted default very minimum limit to avoid rate limit for store.
     */
    public const DEFAULT_MAX_REQUEST_LIMIT = 480;

    /**
     * Magento path to get store domain to pass in headers
     */
    public const XML_PATH_MAGE_CONFIG_STORE_DOMAIN = 'web/secure/base_url';

    public const XML_PATH_CONFIG_SITE_ID = 'athoscommerce/configuration/siteid';
    public const XML_PATH_CONFIG_ENDPOINT = 'athoscommerce/configuration/endpoint';
    public const XML_PATH_CONFIG_SECRET_KEY = 'athoscommerce/configuration/secretkey';

    public const XML_PATH_LIVE_INDEXING_ENABLED = 'athoscommerce/indexing/enable_live_indexing';
    public const XML_PATH_LIVE_INDEXING_TASK_PAYLOAD = 'athoscommerce/indexing/task_payload';
    public const XML_PATH_LIVE_INDEXING_PER_MINUTE = 'athoscommerce/indexing/request_per_minute';
    public const XML_PATH_LIVE_INDEXING_CHUNK_PER_SIZE = 'athoscommerce/indexing/chunk_per_size';

    public const XML_PATH_LIVE_INDEXING_SYNC_CRON_EXPR = 'athoscommerce/indexing/entity_sync_cron_expr';
    public const XML_PATH_LIVE_INDEXING_MILLISECONDS_DELAY = 'athoscommerce/indexing/milliseconds_delay';

    /**
     * Path to debug log enabled configuration
     */
    public const XML_PATH_DEBUG_LOG_ENABLED = 'athoscommerce/developer/enable_debug_log';
}
