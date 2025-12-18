<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Validator;

use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Api\Data\IndexingEntityInterface;
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Validator\ValidatorInterface;
use Magento\Framework\Validator\AbstractValidator;

class IndexingEntityValidator extends AbstractValidator implements ValidatorInterface
{
    /**
     * @var string[]
     */
    private array $fieldTypes = [
        IndexingEntity::TARGET_ENTITY_TYPE => 'string',
        IndexingEntity::TARGET_ID => 'int|integer',
        IndexingEntity::TARGET_PARENT_ID => 'int|integer|null|NULL',
        IndexingEntity::SITE_ID => 'string',
        IndexingEntity::LOCK_TIMESTAMP => 'int|integer|string|null|NULL',
        IndexingEntity::LAST_ACTION => 'string',
        IndexingEntity::LAST_ACTION_TIMESTAMP => 'int|string|null|NULL',
        IndexingEntity::NEXT_ACTION => 'string',
        IndexingEntity::IS_INDEXABLE => 'bool|boolean',
    ];
    /**
     * @var int[]
     */
    private array $maxFieldLengths = [
        IndexingEntity::TARGET_ENTITY_TYPE => 50,
        IndexingEntity::SITE_ID => 20,
    ];

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function isValid(mixed $value): bool
    {
        $this->_clearMessages();

        return $this->validateType($value)
            && $this->validateValuesCorrectType($value)
            && $this->validateVarCharMaxLength($value);
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    private function validateType(mixed $value): bool
    {
        if ($value instanceof IndexingEntityInterface) {
            return true;
        }
        $this->_addMessages([
            __(
                'Invalid type provided. Expected %1, received %2.',
                IndexingEntityInterface::class,
                gettype($value),
            )->render(),
        ]);

        return false;
    }

    /**
     * @param IndexingEntityInterface $indexingEntity
     *
     * @return bool
     */
    private function validateValuesCorrectType(IndexingEntityInterface $indexingEntity): bool
    {
        $return = true;
        foreach ($this->fieldTypes as $field => $allowedTypes) {
            $allowedTypesArray = explode('|', $allowedTypes);

            $dataType = function_exists('get_debug_type') //@phpstan-ignore-line
                ? get_debug_type($indexingEntity->getData($field)) //@phpstan-ignore-line
                : gettype($indexingEntity->getData($field)); //@phpstan-ignore-line

            if (!in_array($dataType, $allowedTypesArray, true)) {
                $return = false;
                $this->_addMessages([
                    __(
                        'Incorrect data type provided for %1. Expected %2, received %3.',
                        $field,
                        $allowedTypes,
                        $dataType,
                    )->render(),
                ]);
            }
        }

        return $return;
    }

    /**
     * Ensure the data we are saving will not be truncated by the database
     *
     * @param IndexingEntityInterface $indexingEntity
     *
     * @return bool
     */
    private function validateVarCharMaxLength(IndexingEntityInterface $indexingEntity): bool
    {
        $return = true;

        foreach ($this->maxFieldLengths as $field => $maxFieldLength) {
            $contentLength = strlen(
                $indexingEntity->getData($field), //@phpstan-ignore-line
            );
            if ($contentLength > $maxFieldLength) {
                $return = false;
                $this->_addMessages([
                    __(
                        'Invalid data provided for %1. Expected max string length %2, received %3.',
                        $field,
                        $maxFieldLength,
                        $contentLength,
                    )->render(),
                ]);
            }
        }

        return $return;
    }
}
