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

namespace AthosCommerce\Feed\Model\Feed\Validator;

use Magento\Framework\Validation\ValidationResult;
use AthosCommerce\Feed\Api\MetadataInterface;
use AthosCommerce\Feed\Model\Task\Validator\CreateValidationResult;
use AthosCommerce\Feed\Model\Task\ValidatorInterface;

class FormatValidator implements ValidatorInterface
{
    /**
     * @var CreateValidationResult
     */
    private $createValidationResult;
    /**
     * @var array
     */
    private $availableFormats;
    /**
     * @var string
     */
    private $field;
    /**
     * @var bool
     */
    private $fieldRequired;

    /**
     * FormatValidator constructor.
     * @param CreateValidationResult $createValidationResult
     * @param array $availableFormats
     * @param string $field
     * @param bool $fieldRequired
     */
    public function __construct(
        CreateValidationResult $createValidationResult,
        array $availableFormats = [MetadataInterface::FORMAT_JSON],
        string $field = 'format',
        bool $fieldRequired = false
    ) {
        $this->createValidationResult = $createValidationResult;
        $this->availableFormats = $availableFormats;
        $this->field = $field;
        $this->fieldRequired = $fieldRequired;
    }

    /**
     * @param array $payload
     * @return ValidationResult
     */
    public function validate(array $payload): ValidationResult
    {
        $errors = [];
        if (!isset($payload[$this->field]) && !$this->fieldRequired) {
            return $this->createValidationResult->create($errors);
        }

        $value = $payload[$this->field];
        if (!in_array($value, $this->availableFormats)) {
            $errors[] = (string) __(
                '"%1" field value must be %2, %3 is not supported',
                $this->field,
                implode(',', $this->availableFormats),
                $value
            );
        }

        return $this->createValidationResult->create($errors);
    }
}
