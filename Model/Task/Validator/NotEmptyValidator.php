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

namespace AthosCommerce\Feed\Model\Task\Validator;

use Magento\Framework\Validation\ValidationResult;
use AthosCommerce\Feed\Model\Task\ValidatorInterface;

class NotEmptyValidator implements ValidatorInterface
{
    /**
     * @var CreateValidationResult
     */
    private $createValidationResult;
    /**
     * @var array
     */
    private $fields;

    /**
     * IntValidator constructor.
     * @param CreateValidationResult $createValidationResult
     * @param array $fields
     */
    public function __construct(
        CreateValidationResult $createValidationResult,
        array $fields = []
    ) {
        $this->createValidationResult = $createValidationResult;
        $this->fields = $fields;
    }

    /**
     * @param array $payload
     * @return ValidationResult
     */
    public function validate(array $payload): ValidationResult
    {
        $errors = [];
        foreach ($this->fields as $field) {
            if (!array_key_exists($field, $payload)
                || !isset($payload[$field])
                || (is_string($payload[$field]) && $payload[$field] !== '0' && empty($payload[$field]))
            ) {
                $errors[] = (string)__('"%1" field value must be not empty', $field);
            }
        }

        return $this->createValidationResult->create($errors);
    }
}
