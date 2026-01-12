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

namespace AthosCommerce\Feed\Model;

use AthosCommerce\Feed\Model\Api\MagentoEntityInterface;

class MagentoEntity implements MagentoEntityInterface
{
    /**
     * @var int
     */
    private $entityId;
    /**
     * @var string
     */
    private $siteId;
    /**
     * @var bool
     */
    private $isIndexable;
    /**
     * @var int|null
     */
    private $entityParentId;
    /**
     * @var string|null
     */
    private $entitySubtype;

    /**
     * @param int $entityId
     * @param string $siteId
     * @param bool $isIndexable
     * @param int|null $entityParentId
     * @param string|null $entitySubtype
     */
    public function __construct(
        int $entityId,
        string $siteId,
        bool $isIndexable,
        ?int $entityParentId = null,
        ?string $entitySubtype = null
    ) {
        $this->entityId = $entityId;
        $this->siteId = $siteId;
        $this->isIndexable = $isIndexable;
        $this->entityParentId = $entityParentId;
        $this->entitySubtype = $entitySubtype;
    }

    /**
     * @return int
     */
    public function getEntityId(): int
    {
        return $this->entityId;
    }

    /**
     * @return int|null
     */
    public function getEntityParentId(): ?int
    {
        return $this->entityParentId;
    }

    /**
     * @return string|null
     */
    public function getEntitySubtype(): ?string
    {
        return $this->entitySubtype;
    }

    /**
     * @return string
     */
    public function getSiteId(): string
    {
        return $this->siteId;
    }

    /**
     * @return bool
     */
    public function isIndexable(): bool
    {
        return $this->isIndexable;
    }

    /**
     * @param bool $isIndexable
     *
     * @return void
     */
    public function setIsIndexable(bool $isIndexable): void
    {
        $this->isIndexable = $isIndexable;
    }
}
