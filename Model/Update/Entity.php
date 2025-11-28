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

namespace AthosCommerce\Feed\Model\Update;

use AthosCommerce\Feed\Model\Update\EntityInterface;

class Entity implements EntityInterface
{
    const ENTITY_TYPE = 'entityType';
    const ENTITY_IDS = 'entityIds';

    /**
     * @var string
     */
    private string $entityType;
    /**
     * @var int[]
     */
    private array $entityIds;

    /**
     * @param mixed[] $data
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(array $data)
    {
        array_walk($data, [$this, 'setData']);
    }

    /**
     * @return string
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * @param string $entityType
     *
     * @return void
     */
    public function setEntityType(string $entityType): void
    {
        $this->entityType = $entityType;
    }

    /**
     * @return int[]
     */
    public function getEntityIds(): array
    {
        return $this->entityIds;
    }

    /**
     * @param int[] $entityIds
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setEntityIds(array $entityIds): void
    {
        array_walk(
            $entityIds,
            [$this, 'validateIsInt'],
            static::ENTITY_IDS
        );
        $this->entityIds = $entityIds;
    }

    /**
     * @param mixed $value
     * @param string $key
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    private function setData(mixed $value, string $key): void
    {
        switch ($key) {
            case static::ENTITY_TYPE:
                $this->setEntityType($value);
                break;
            case static::ENTITY_IDS:
                $this->setEntityIds($value);
                break;
            case static::STORE_IDS:
                $this->setStoreIds($value);
                break;
            default:
                throw new \InvalidArgumentException(
                    sprintf(
                        'Invalid key provided in creation of %s. Key %s',
                        $this::class,
                        $key,
                    ),
                );
        }
    }

    /**
     * @param mixed $value
     * @param mixed $key
     * @param string $attribute
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    private function validateIsInt(mixed $value, mixed $key, string $attribute): void
    {
        if (is_int($value)) {
            return;
        }
        throw new \InvalidArgumentException(
            sprintf(
                'Invalid value supplied for %s at position %s. Expects int, received %s',
                $attribute,
                $key,
                get_debug_type($value),
            ),
        );
    }
}
