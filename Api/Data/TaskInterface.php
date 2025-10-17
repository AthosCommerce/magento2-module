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

namespace AthosCommerce\Feed\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface TaskInterface extends ExtensibleDataInterface
{
    /**
     *
     */
    const ENTITY_ID = 'entity_id';
    /**
     *
     */
    const TYPE = 'type';
    /**
     *
     */
    const STATUS = 'status';
    /**
     *
     */
    const PAYLOAD = 'payload';
    /**
     *
     */
    const ERROR = 'error';
    /**
     *
     */
    const CREATED_AT = 'created_at';
    /**
     *
     */
    const STARTED_AT = 'started_at';
    /**
     *
     */
    const ENDED_AT = 'ended_at';
    /**
     *
     */
    const Product_Count = 'product_count';
    /**
     *
     */
    const File_Size = 'file_size';


    /**
     * @return mixed
     */
    public function getEntityId();

    /**
     * @param $id
     * @return mixed
     */
    public function setEntityId($id);

    /**
     * @return string|null
     */
    public function getType() : ?string;

    /**
     * @param string $type
     * @return TaskInterface
     */
    public function setType(string $type) : self;

    /**
     * @return string|null
     */
    public function getStatus() : ?string;

    /**
     * @param string $status
     * @return TaskInterface
     */
    public function setStatus(string $status) : self;

    /**
     * @return string[]
     */
    public function getPayload() : array;

    /**
     * @param array $payload
     * @return TaskInterface
     */
    public function setPayload(array $payload) : self;

    /**
     * @return string|null
     */
    public function getCreatedAt() : ?string;

    /**
     * @param string $date
     * @return TaskInterface
     */
    public function setCreatedAt(string $date) : self;

    /**
     * @return string|null
     */
    public function getStartedAt() : ?string;

    /**
     * @param string $date
     * @return TaskInterface
     */
    public function setStartedAt(string $date) : self;

    /**
     * @return string|null
     */
    public function getEndedAt() : ?string;

    /**
     * @param string $date
     * @return TaskInterface
     */
    public function setEndedAt(string $date) : self;

    /**
     * @return \AthosCommerce\Feed\Api\Data\TaskErrorInterface|null
     */
    public function getError() : ?TaskErrorInterface;

    /**
     * @param \AthosCommerce\Feed\Api\Data\TaskErrorInterface $error
     * @return TaskInterface
     */
    public function setError(TaskErrorInterface $error) : self;

    /**
     * @return \AthosCommerce\Feed\Api\Data\TaskExtensionInterface|null
     */
    public function getExtensionAttributes(): ?\AthosCommerce\Feed\Api\Data\TaskExtensionInterface;

    /**
     * @param \AthosCommerce\Feed\Api\Data\TaskExtensionInterface $extensionAttributes
     * @return TaskInterface
     */
    public function setExtensionAttributes(
        \AthosCommerce\Feed\Api\Data\TaskExtensionInterface $extensionAttributes
    ): self;

    /**
     * @return int|null
     */
    public function getProductCount(): ?int;

    /**
     * @return int
     */
    public function getFileSize(): int;

    /**
     * @param int $value
     * @return TaskInterface
     */
    public function setProductCount(int $value): self;

    /**
     * @param int $value
     * @return TaskInterface
     */
    public function setFileSize(int $value): self;
}
