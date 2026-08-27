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

class SensitiveDataMasker
{
    /**
     * @var string
     */
    public const REDACTED_VALUE = '****';

    /**
     * @var string[]
     */
    public const DEFAULT_SENSITIVE_KEYS = [
        'secretKey',
        'preSignedUrl',
        'catalogPreSignedUrl',
    ];

    /**
     * @param mixed $value
     * @param string[] $sensitiveKeys
     * @param string $redactedValue
     * @return mixed
     */
    public static function mask(
        $value,
        array $sensitiveKeys = self::DEFAULT_SENSITIVE_KEYS,
        string $redactedValue = self::REDACTED_VALUE
    ) {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            if (in_array((string)$key, $sensitiveKeys, true) && $item !== null) {
                $value[$key] = $redactedValue;
                continue;
            }

            if (is_array($item)) {
                $value[$key] = self::mask($item, $sensitiveKeys, $redactedValue);
            }
        }

        return $value;
    }
}

