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
use Magento\Framework\Validation\ValidationResultFactory;
use AthosCommerce\Feed\Model\Task\ValidatorInterface;

class ValidatorComposite implements ValidatorInterface
{
    /**
     * @var array
     */
    private $validators;
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * ValidatorComposite constructor.
     * @param ValidationResultFactory $validationResultFactory
     * @param array $validators
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        array $validators = []
    ) {
        $this->validators = $validators;
        $this->validationResultFactory = $validationResultFactory;
    }

    /**
     * @param array $payload
     * @return ValidationResult
     */
    public function validate(array $payload): ValidationResult
    {
        $sortedValidators = $this->sort($this->validators);
        $errors = [];
        foreach ($sortedValidators as $sortedValidator) {
            if (isset($sortedValidator['objectInstance'])) {
                /** @var ValidatorInterface $instance */
                $instance = $sortedValidator['objectInstance'];
                $validationResult = $instance->validate($payload);
                if ($validationResult->isValid()) {
                    continue;
                }

                $errors = array_merge($errors, $validationResult->getErrors());
                $shouldBreak = $sortedValidator['breakOnFailure'] ?? true;
                if ($shouldBreak) {
                    break;
                }
            }
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }


    /**
     * @param array $data
     * @return array
     */
    private function sort(array $data)
    {
        usort($data, function (array $a, array $b) {
            return $this->getSortOrder($a) <=> $this->getSortOrder($b);
        });

        return $data;
    }

    /**
     * @param array $variable
     * @return int
     */
    private function getSortOrder(array $variable)
    {
        return !empty($variable['sortOrder']) ? (int) $variable['sortOrder'] : 0;
    }
}
