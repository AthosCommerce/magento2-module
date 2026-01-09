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

namespace AthosCommerce\Feed\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use AthosCommerce\Feed\Api\Data\CronStatusInterface;

class CronStatus extends AbstractExtensibleObject implements CronStatusInterface
{
    public const SCHEDULE_ID = 'schedule_id';
    public const JOB_CODE = 'job_code';
    public const STATUS = 'status';
    public const MESSAGES = 'messages';
    public const CREATED_AT = 'created_at';
    public const SCHEDULED_AT = 'scheduled_at';
    public const EXECUTED_AT = 'executed_at';
    public const FINISHED_AT = 'finished_at';

    /**
     * Get schedule ID
     *
     * @return int|null
     */
    public function getScheduleId(): ?int
    {
        return $this->_get(self::SCHEDULE_ID) ? (int)$this->_get(self::SCHEDULE_ID) : null;
    }

    /**
     * Set schedule ID
     *
     * @param int $scheduleId
     * @return $this
     */
    public function setScheduleId(int $scheduleId): self
    {
        return $this->setData(self::SCHEDULE_ID, $scheduleId);
    }

    /**
     * Get job code
     *
     * @return string|null
     */
    public function getJobCode(): ?string
    {
        return $this->_get(self::JOB_CODE);
    }

    /**
     * Set job code
     *
     * @param string $jobCode
     * @return $this
     */
    public function setJobCode(string $jobCode): self
    {
        return $this->setData(self::JOB_CODE, $jobCode);
    }

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->_get(self::STATUS);
    }

    /**
     * Set status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get messages
     *
     * @return string|null
     */
    public function getMessages(): ?string
    {
        return $this->_get(self::MESSAGES);
    }

    /**
     * Set messages
     *
     * @param string $messages
     * @return $this
     */
    public function setMessages(string $messages): self
    {
        return $this->setData(self::MESSAGES, $messages);
    }

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->_get(self::CREATED_AT);
    }

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get scheduled at
     *
     * @return string|null
     */
    public function getScheduledAt(): ?string
    {
        return $this->_get(self::SCHEDULED_AT);
    }

    /**
     * Set scheduled at
     *
     * @param string $scheduledAt
     * @return $this
     */
    public function setScheduledAt(string $scheduledAt): self
    {
        return $this->setData(self::SCHEDULED_AT, $scheduledAt);
    }

    /**
     * Get executed at
     *
     * @return string|null
     */
    public function getExecutedAt(): ?string
    {
        return $this->_get(self::EXECUTED_AT);
    }

    /**
     * Set executed at
     *
     * @param string $executedAt
     * @return $this
     */
    public function setExecutedAt(string $executedAt): self
    {
        return $this->setData(self::EXECUTED_AT, $executedAt);
    }

    /**
     * Get finished at
     *
     * @return string|null
     */
    public function getFinishedAt(): ?string
    {
        return $this->_get(self::FINISHED_AT);
    }

    /**
     * Set finished at
     *
     * @param string $finishedAt
     * @return $this
     */
    public function setFinishedAt(string $finishedAt): self
    {
        return $this->setData(self::FINISHED_AT, $finishedAt);
    }
}
