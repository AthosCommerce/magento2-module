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

namespace AthosCommerce\Feed\Model\Api;

use AthosCommerce\Feed\Api\Data\CronStatusInterface;
use AthosCommerce\Feed\Api\Data\CronStatusInterfaceFactory;
use AthosCommerce\Feed\Api\Data\CronStatusResponseInterface;
use AthosCommerce\Feed\Api\Data\CronStatusResponseInterfaceFactory;
use AthosCommerce\Feed\Api\GetCronStatusInterface;
use Magento\Cron\Model\ResourceModel\Schedule\Collection as ScheduleCollection;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory as ScheduleCollectionFactory;
use Magento\Cron\Model\Schedule;
use Magento\Framework\Stdlib\DateTime\DateTime;

class GetCronStatus implements GetCronStatusInterface
{
    private const JOB_CODES = [
        'athoscommerce_task_execution',
        'athoscommerce_live_indexing_discovery',
        'athoscommerce_live_indexing_sync',
    ];

    private const RECENT_RUN_LIMIT = 3;

    private const RUNNING_THRESHOLD_SECONDS = 600;

    /**
     * @var ScheduleCollectionFactory
     */
    private $scheduleCollectionFactory;

    /**
     * @var CronStatusResponseInterfaceFactory
     */
    private $responseFactory;

    /**
     * @var CronStatusInterfaceFactory
     */
    private $cronStatusFactory;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param ScheduleCollectionFactory $scheduleCollectionFactory
     * @param CronStatusResponseInterfaceFactory $responseFactory
     * @param CronStatusInterfaceFactory $cronStatusFactory
     * @param DateTime $dateTime
     */
    public function __construct(
        ScheduleCollectionFactory $scheduleCollectionFactory,
        CronStatusResponseInterfaceFactory $responseFactory,
        CronStatusInterfaceFactory $cronStatusFactory,
        DateTime $dateTime
    ) {
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
        $this->responseFactory = $responseFactory;
        $this->cronStatusFactory = $cronStatusFactory;
        $this->dateTime = $dateTime;
    }

    /**
     * Get the recent AthosCommerce cron status summary.
     *
     * @return CronStatusResponseInterface
     */
    public function getList(): CronStatusResponseInterface
    {
        /** @var CronStatusResponseInterface $response */
        $response = $this->responseFactory->create();
        $recentCollection = $this->getBaseCollection();
        $recentCollection->setOrder('scheduled_at', ScheduleCollection::SORT_ORDER_DESC);
        $recentCollection->setOrder('schedule_id', ScheduleCollection::SORT_ORDER_DESC);
        $recentCollection->setPageSize(self::RECENT_RUN_LIMIT);
        $recentCollection->setCurPage(1);

        $cronJobs = [];
        $lastStatus = null;
        /** @var Schedule $scheduleItem */
        foreach ($recentCollection->getItems() as $index => $scheduleItem) {
            $cronJob = $this->cronStatusFactory->create();
            $cronJob->setScheduleId((int) $scheduleItem->getScheduleId());
            $cronJob->setJobCode((string) $scheduleItem->getJobCode());
            $cronJob->setStatus((string) $scheduleItem->getStatus());
            $cronJob->setMessages((string) ($scheduleItem->getMessages() ?: ''));
            $cronJob->setCreatedAt((string) $scheduleItem->getCreatedAt());
            $cronJob->setScheduledAt((string) $scheduleItem->getScheduledAt());
            $cronJob->setExecutedAt((string) ($scheduleItem->getExecutedAt() ?: ''));
            $cronJob->setFinishedAt((string) ($scheduleItem->getFinishedAt() ?: ''));
            $cronJobs[] = $cronJob;

            if ($index === 0) {
                $lastStatus = $cronJob->getStatus();
            }
        }

        $lastSuccessAt = $this->getLastSuccessAt();

        return $response
            ->setIsRunning($this->isRecentSuccess($lastSuccessAt))
            ->setLastSuccessAt($lastSuccessAt)
            ->setLastStatus($lastStatus)
            ->setCronJobs($cronJobs);
    }

    /**
     * Create the base collection for AthosCommerce cron jobs.
     *
     * @return ScheduleCollection
     */
    private function getBaseCollection(): ScheduleCollection
    {
        $collection = $this->scheduleCollectionFactory->create();
        $collection->addFieldToFilter(
            'job_code',
            array_map(
                static function (string $jobCode): array {
                    return ['eq' => $jobCode];
                },
                self::JOB_CODES
            )
        );

        return $collection;
    }

    /**
     * Get the latest successful AthosCommerce cron timestamp.
     *
     * @return string|null
     */
    private function getLastSuccessAt(): ?string
    {
        $successCollection = $this->getBaseCollection();
        $successCollection->addFieldToFilter('status', 'success');
        $successCollection->setOrder('scheduled_at', ScheduleCollection::SORT_ORDER_DESC);
        $successCollection->setOrder('schedule_id', ScheduleCollection::SORT_ORDER_DESC);
        $successCollection->setPageSize(1);
        $successCollection->setCurPage(1);

        /** @var Schedule|null $successSchedule */
        $successSchedule = $successCollection->getFirstItem();
        if (!$successSchedule || !$successSchedule->getId()) {
            return null;
        }

        return (string) (
            $successSchedule->getFinishedAt()
            ?: $successSchedule->getExecutedAt()
            ?: $successSchedule->getScheduledAt()
        );
    }

    /**
     * Determine whether the latest success is within the running threshold.
     *
     * @param string|null $lastSuccessAt
     *
     * @return bool
     */
    private function isRecentSuccess(?string $lastSuccessAt): bool
    {
        if ($lastSuccessAt === null || $lastSuccessAt === '') {
            return false;
        }

        $successTimestamp = strtotime($lastSuccessAt);
        $currentTimestamp = strtotime($this->dateTime->gmtDate());
        if ($successTimestamp === false || $currentTimestamp === false) {
            return false;
        }

        return ($currentTimestamp - $successTimestamp) <= self::RUNNING_THRESHOLD_SECONDS;
    }
}
