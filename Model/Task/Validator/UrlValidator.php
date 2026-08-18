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

namespace AthosCommerce\Feed\Model\Task\Validator;

use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validator\Url;
use AthosCommerce\Feed\Model\Task\ValidatorInterface;

class UrlValidator implements ValidatorInterface
{
    /**
     * @var CreateValidationResult
     */
    private $createValidationResult;

    /**
     * @var bool
     */
    private $fieldRequired;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var Url
     */
    private $urlValidator;

    /**
     * @param CreateValidationResult $createValidationResult
     * @param Url $urlValidator
     * @param array $fields
     * @param bool $fieldRequired
     */
    public function __construct(
        CreateValidationResult $createValidationResult,
        Url $urlValidator,
        array $fields = [],
        bool $fieldRequired = false
    ) {
        $this->createValidationResult = $createValidationResult;
        $this->fieldRequired = $fieldRequired;
        $this->fields = $fields;
        $this->urlValidator = $urlValidator;
    }

    /**
     * @param array $payload
     * @return ValidationResult
     */
    public function validate(array $payload): ValidationResult
    {
        $errors = [];

        foreach ($this->fields as $field) {
            $hasField = array_key_exists($field, $payload);
            $value = $payload[$field] ?? null;

            // Handle missing or empty optional/required fields
            if (!$hasField || $value === null || $value === '') {
                if ($this->fieldRequired) {
                    $errors[] = (string) __('%1 field is required', $field);
                }
                continue;
            }

            // Enforce standard URL format
            if (!$this->urlValidator->isValid((string) $value, ['http', 'https'])) {
                $errors[] = (string) __('"%1" field value must be valid url address', $field);
                continue; // Skip host check if basic URL format is invalid
            }

            // Enforce AWS Bucket host format
            if (!$this->isAllowedAmazonAwsHost((string) $value)) {
                $errors[] = (string) __('"%1" field value must contain valid bucket url', $field);
            }
        }

        return $this->createValidationResult->create($errors);
    }

    /**
     * @param string $url
     * @return bool
     */
    private function isAllowedAmazonAwsHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return false;
        }

        $normalizedHost = strtolower(rtrim($host, '.'));
        $targetDomain = 'amazonaws.com';

        if ($normalizedHost === $targetDomain) {
            return true;
        }

        $suffix = '.' . $targetDomain;

        if (function_exists('str_ends_with')) {
            return str_ends_with($normalizedHost, $suffix);
        }

        $suffixLength = strlen($suffix);
        if (strlen($normalizedHost) <= $suffixLength) {
            return false;
        }

        return substr($normalizedHost, -$suffixLength) === $suffix;
    }
}