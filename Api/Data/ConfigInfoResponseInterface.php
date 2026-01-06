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

namespace AthosCommerce\Feed\Api\Data;

interface ConfigInfoResponseInterface
{
    /**
     * @return bool
     */
    public function getSuccess(): bool;

    /**
     * @param bool $success
     * @return self
     */
    public function setSuccess(bool $success): self;

    /**
     * @return string|null
     */
    public function getMessage(): ?string;

    /**
     * @param string|null $message
     * @return self
     */
    public function setMessage(?string $message): self;

    /**
     * @return \AthosCommerce\Feed\Api\Data\StoreConfigInterface[]
     */
    public function getStores(): array;

    /**
     * @param \AthosCommerce\Feed\Api\Data\StoreConfigInterface[] $stores
     * @return self
     */
    public function setStores(array $stores): self;
}
