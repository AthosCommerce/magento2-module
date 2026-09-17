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

use AthosCommerce\Feed\Api\Data\CronStatusResponseInterface;
use Magento\Framework\DataObject;

class CronStatusResponse extends DataObject implements CronStatusResponseInterface
{
    /**
     * Get the per-job summaries.
     *
     * @return \AthosCommerce\Feed\Api\Data\CronJobSummaryInterface[]
     */
    public function getJobSummaries(): array
    {
        return $this->getData('job_summaries') ?? [];
    }

    /**
     * Set the per-job summaries.
     *
     * @param \AthosCommerce\Feed\Api\Data\CronJobSummaryInterface[] $jobSummaries
     *
     * @return self
     */
    public function setJobSummaries(array $jobSummaries): self
    {
        return $this->setData('job_summaries', $jobSummaries);
    }

    /**
     * Get the status of the most recent cron row.
     *
     * @return string|null
     */
    public function getLastStatus(): ?string
    {
        return $this->getData('last_status');
    }

    /**
     * Set the status of the most recent cron row.
     *
     * @param string|null $lastStatus
     *
     * @return self
     */
    public function setLastStatus(?string $lastStatus): self
    {
        return $this->setData('last_status', $lastStatus);
    }

    /**
     * Get the most recent cron jobs.
     *
     * @return \AthosCommerce\Feed\Api\Data\CronStatusInterface[]
     */
    public function getCronJobs(): array
    {
        return $this->getData('cron_jobs') ?? [];
    }

    /**
     * Set the most recent cron jobs.
     *
     * @param \AthosCommerce\Feed\Api\Data\CronStatusInterface[] $cronJobs
     *
     * @return self
     */
    public function setCronJobs(array $cronJobs): self
    {
        return $this->setData('cron_jobs', $cronJobs);
    }
}
