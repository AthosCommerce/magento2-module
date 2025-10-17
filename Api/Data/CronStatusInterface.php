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

interface CronStatusInterface
{
    /**
     * Get schedule ID
     *
     * @return int|null
     */
    public function getScheduleId(): ?int;

    /**
     * Set schedule ID
     *
     * @param int $scheduleId
     * @return $this
     */
    public function setScheduleId(int $scheduleId): self;

    /**
     * Get job code
     *
     * @return string|null
     */
    public function getJobCode(): ?string;

    /**
     * Set job code
     *
     * @param string $jobCode
     * @return $this
     */
    public function setJobCode(string $jobCode): self;

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * Set status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self;

    /**
     * Get messages
     *
     * @return string|null
     */
    public function getMessages(): ?string;

    /**
     * Set messages
     *
     * @param string $messages
     * @return $this
     */
    public function setMessages(string $messages): self;

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * Get scheduled at
     *
     * @return string|null
     */
    public function getScheduledAt(): ?string;

    /**
     * Set scheduled at
     *
     * @param string $scheduledAt
     * @return $this
     */
    public function setScheduledAt(string $scheduledAt): self;

    /**
     * Get executed at
     *
     * @return string|null
     */
    public function getExecutedAt(): ?string;

    /**
     * Set executed at
     *
     * @param string $executedAt
     * @return $this
     */
    public function setExecutedAt(string $executedAt): self;

    /**
     * Get finished at
     *
     * @return string|null
     */
    public function getFinishedAt(): ?string;

    /**
     * Set finished at
     *
     * @param string $finishedAt
     * @return $this
     */
    public function setFinishedAt(string $finishedAt): self;
}
