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

interface CronJobSummaryInterface
{
    /**
     * Get the cron job code.
     *
     * @return string
     */
    public function getJobCode(): string;

    /**
     * Set the cron job code.
     *
     * @param string $jobCode
     *
     * @return self
     */
    public function setJobCode(string $jobCode): self;

    /**
     * Get the latest status for the job code.
     *
     * @return string
     */
    public function getLastStatus(): string;

    /**
     * Set the latest status for the job code.
     *
     * @param string $lastStatus
     *
     * @return self
     */
    public function setLastStatus(string $lastStatus): self;

    /**
     * Get the latest successful execution timestamp for the job code.
     *
     * @return string
     */
    public function getLastSuccessAt(): string;

    /**
     * Set the latest successful execution timestamp for the job code.
     *
     * @param string $lastSuccessAt
     *
     * @return self
     */
    public function setLastSuccessAt(string $lastSuccessAt): self;
}
