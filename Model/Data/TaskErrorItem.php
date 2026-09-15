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

namespace AthosCommerce\Feed\Model\Data;

use AthosCommerce\Feed\Api\Data\TaskErrorItemInterface;

class TaskErrorItem extends TaskError implements TaskErrorItemInterface
{
    /**
     * Get the task ID.
     *
     * @return int
     */
    public function getTaskId(): int
    {
        return (int)$this->_get(self::TASK_ID);
    }

    /**
     * Set the task ID.
     *
     * @param int $taskId
     * @return TaskErrorItemInterface
     */
    public function setTaskId(int $taskId): TaskErrorItemInterface
    {
        return $this->setData(self::TASK_ID, $taskId);
    }

    /**
     * Get the error creation timestamp.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->_get(self::CREATED_AT);
    }

    /**
     * Set the error creation timestamp.
     *
     * @param string|null $createdAt
     * @return TaskErrorItemInterface
     */
    public function setCreatedAt(?string $createdAt): TaskErrorItemInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
