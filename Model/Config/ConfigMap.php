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

namespace AthosCommerce\Feed\Model\Config;

use AthosCommerce\Feed\Helper\Constants;

class ConfigMap
{
    public const MAP = [
        'siteId' => [
            'path' => Constants::XML_PATH_CONFIG_SITE_ID,
            'type' => 'string',
            'validator' => 'validateString'
        ],
        'endPoint' => [
            'path' => Constants::XML_PATH_CONFIG_ENDPOINT,
            'type' => 'string',
            'validator' => 'validateEndpoint'
        ],
        'secretKey' => [
            'path' => Constants::XML_PATH_CONFIG_SECRET_KEY,
            'type' => 'secret',
            'encrypt' => true,
            'validator' => 'validateSecretKey'
        ],
        'shopDomain' => [
            'path' => Constants::XML_PATH_CONFIG_SHOP_DOMAIN,
            'type' => 'string',
            'validator' => 'validateString'
        ],
        'feedId' => [
            'path' => Constants::XML_PATH_CONFIG_FEED_ID,
            'type' => 'string',
            'validator' => 'validateNumber'
        ],
        'enableLiveIndexing' => [
            'path' => Constants::XML_PATH_LIVE_INDEXING_ENABLED,
            'type' => 'bool',
            'validator' => 'validateBoolean'
        ],
        'entitySyncCronExpr' => [
            'path' => Constants::XML_PATH_LIVE_INDEXING_SYNC_CRON_EXPR,
            'type' => 'cron',
            'validator' => 'validateCron'
        ],
        'perMinute' => [
            'path' => Constants::XML_PATH_LIVE_INDEXING_PER_MINUTE,
            'type' => 'int',
            'validator' => 'validateNumber'
        ],
        'chunkSize' => [
            'path' => Constants::XML_PATH_LIVE_INDEXING_CHUNK_PER_SIZE,
            'type' => 'int',
            'validator' => 'validateNumber'
        ],
    ];

    /**
     * @return array|null
     */
    public static function getPathToKeyMap(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        foreach (self::MAP as $key => $config) {
            $cache[$config['path']] = $key;
        }

        return $cache;
    }
}
