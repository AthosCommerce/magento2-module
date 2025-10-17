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

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationResult;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreIsInactiveException;
use AthosCommerce\Feed\Model\Task\ValidatorInterface;

class StoreValidator implements ValidatorInterface
{
    /**
     * @var CreateValidationResult
     */
    private $createValidationResult;
    /**
     * @var string
     */
    private $field;
    /**
     * @var bool
     */
    private $fieldRequired;
    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * StoreValidator constructor.
     * @param CreateValidationResult $createValidationResult
     * @param StoreRepositoryInterface $storeRepository
     * @param string $field
     * @param bool $fieldRequired
     */
    public function __construct(
        CreateValidationResult $createValidationResult,
        StoreRepositoryInterface $storeRepository,
        string $field = 'store',
        bool $fieldRequired = false
    ) {
        $this->createValidationResult = $createValidationResult;
        $this->field = $field;
        $this->fieldRequired = $fieldRequired;
        $this->storeRepository = $storeRepository;
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

        $value = $payload[$this->field];
        try {
            $this->storeRepository->getActiveStoreByCode($value);
        } catch (NoSuchEntityException $exception) {
            $errors[] = (string) __('Store "%1" doesn\'t exist', $value);
        } catch (StoreIsInactiveException $exception) {
            $errors[] = (string) __('Store "%1" is not active', $value);
        }

        return $this->createValidationResult->create($errors);
    }
}
