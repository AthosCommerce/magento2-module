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

namespace AthosCommerce\Feed\Helper;

class EndpointUrlValidator
{
    /**
     * Endpoint must belong to AthosCommerce domains.
     */
    private const ALLOWED_ENDPOINT_SUFFIXES = [
        '.athoscommerce.net',
    ];

    /**
     * @param string|null $endpoint
     * @return string|null
     */
    public static function normalizeAndValidate(?string $endpoint): ?string
    {
        if ($endpoint === null) {
            return null;
        }

        $value = trim($endpoint);
        if ($value === '') {
            return null;
        }

        if (parse_url($value, PHP_URL_SCHEME) === null) {
            $value = 'https://' . $value;
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return null;
        }

        $scheme = strtolower((string)parse_url($value, PHP_URL_SCHEME)); // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $host = strtolower((string)parse_url($value, PHP_URL_HOST)); // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if ($scheme !== 'https' || $host === '') {
            return null;
        }

        foreach (self::ALLOWED_ENDPOINT_SUFFIXES as $suffix) {
            if (substr($host, -strlen($suffix)) === $suffix) {
                return rtrim($value, '/');
            }
        }

        return null;
    }
}
