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

class S3UrlValidator
{
    /**
     * S3 virtual-hosted-style endpoints accepted by AWS for bucket uploads.
     */
    private const HOST_PATTERNS = [
        '/^(?P<bucket>[a-z0-9][a-z0-9.-]*[a-z0-9])\.s3\.amazonaws\.com$/',
        '/^(?P<bucket>[a-z0-9][a-z0-9.-]*[a-z0-9])\.s3[.-][a-z0-9-]+\.amazonaws\.com$/',
        '/^(?P<bucket>[a-z0-9][a-z0-9.-]*[a-z0-9])\.s3\.dualstack\.[a-z0-9-]+\.amazonaws\.com$/',
    ];

    /**
     * Validate that the URL points to an HTTPS S3 bucket host.
     *
     * @param string|null $url
     * @return bool
     */
    public function validate(?string $url): bool
    {
        if ($url === null) {
            return false;
        }

        $value = trim($url);
        if ($value === '' || !filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }

        if (parse_url($value, PHP_URL_USER) !== null // phpcs:ignore Magento2.Functions.DiscouragedFunction
            || parse_url($value, PHP_URL_PASS) !== null // phpcs:ignore Magento2.Functions.DiscouragedFunction
            || parse_url($value, PHP_URL_PORT) !== null // phpcs:ignore Magento2.Functions.DiscouragedFunction
        ) {
            return false;
        }

        $scheme = strtolower(
            (string) parse_url($value, PHP_URL_SCHEME) // phpcs:ignore Magento2.Functions.DiscouragedFunction
        );
        $host = strtolower(
            rtrim(
                (string) parse_url($value, PHP_URL_HOST), // phpcs:ignore Magento2.Functions.DiscouragedFunction
                '.'
            )
        );

        if ($scheme !== 'https' || $host === '') {
            return false;
        }

        return $this->isAllowedAmazonAwsHost($value);
    }

    /**
     * Check that the URL host matches an S3 virtual-hosted bucket endpoint.
     *
     * @param string $url
     * @return bool
     */
    private function isAllowedAmazonAwsHost(string $url): bool
    {
        $host = strtolower(
            rtrim(
                (string) parse_url($url, PHP_URL_HOST), // phpcs:ignore Magento2.Functions.DiscouragedFunction
                '.'
            )
        );

        foreach (self::HOST_PATTERNS as $pattern) {
            if (!preg_match($pattern, $host, $matches)) {
                continue;
            }

            $bucket = $matches['bucket'] ?? null;
            if (!is_string($bucket) || !$this->isValidBucketName($bucket)) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Validate that the extracted bucket name follows AWS rules.
     *
     * @param string $bucket
     * @return bool
     */
    private function isValidBucketName(string $bucket): bool
    {
        $length = strlen($bucket);
        if ($length < 3 || $length > 63) {
            return false;
        }

        if (!preg_match('/^[a-z0-9][a-z0-9.-]*[a-z0-9]$/', $bucket)) {
            return false;
        }

        if (strpos($bucket, '..') !== false) {
            return false;
        }

        return !preg_match('/^(?:\d{1,3}\.){3}\d{1,3}$/', $bucket);
    }
}
