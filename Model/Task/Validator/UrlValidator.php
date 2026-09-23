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

use AthosCommerce\Feed\Helper\S3UrlValidator;
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
     * @var S3UrlValidator
     */
    private $s3UrlValidator;

    /**
     * @param CreateValidationResult $createValidationResult
     * @param Url $urlValidator
     * @param S3UrlValidator $s3UrlValidator
     * @param array $fields
     * @param bool $fieldRequired
     */
    public function __construct(
        CreateValidationResult $createValidationResult,
        Url $urlValidator,
        S3UrlValidator $s3UrlValidator,
        array $fields = [],
        bool $fieldRequired = false
    ) {
        $this->createValidationResult = $createValidationResult;
        $this->fieldRequired = $fieldRequired;
        $this->fields = $fields;
        $this->urlValidator = $urlValidator;
        $this->s3UrlValidator = $s3UrlValidator;
    }

    /**
     * Validate configured URL fields in the task payload.
     *
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
            if (!$this->urlValidator->isValid((string) $value, ['https'])) {
                $errors[] = (string) __('"%1" field value must be valid url address', $field);
                continue; // Skip host check if basic URL format is invalid
            }

            // Enforce AWS Bucket host format
            if (!$this->s3UrlValidator->validate((string)$value)) {
                $errors[] = (string) __('"%1" field value must contain valid bucket url', $field);
            }
        }

        return $this->createValidationResult->create($errors);
    }
}
