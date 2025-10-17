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

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationResult;
use AthosCommerce\Feed\Model\Task\ValidatorInterface;

class CustomerValidator implements ValidatorInterface
{
    /**
     * @var CreateValidationResult
     */
    private $createValidationResult;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var string
     */
    private $field;
    /**
     * @var bool
     */
    private $fieldRequired;

    /**
     * CustomerValidator constructor.
     * @param CreateValidationResult $createValidationResult
     * @param CustomerRepositoryInterface $customerRepository
     * @param string $field
     * @param bool $fieldRequired
     */
    public function __construct(
        CreateValidationResult $createValidationResult,
        CustomerRepositoryInterface $customerRepository,
        string $field = 'customerId',
        bool $fieldRequired = false
    ) {
        $this->createValidationResult = $createValidationResult;
        $this->customerRepository = $customerRepository;
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
        } elseif (!isset($payload[$this->field]) && $this->fieldRequired) {
            return $this->createValidationResult->create([(string) __('%1 field is required', $this->field)]);
        }

        $customerId = $payload[$this->field];
        if (is_null($customerId) || is_bool($customerId)) {
            return $this->createValidationResult->create($errors);
        }

        try {
            $this->customerRepository->getById((int) $customerId);
        } catch (NoSuchEntityException | LocalizedException $exception) {
            $errors[] = $exception->getMessage();
        }

        return $this->createValidationResult->create($errors);
    }
}
