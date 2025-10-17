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

use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory as ScheduleCollectionFactory;
use AthosCommerce\Feed\Api\GetCronStatusInterface;

class GetCronStatus implements GetCronStatusInterface
{
    /**
     * @var ScheduleCollectionFactory
     */
    private $scheduleCollectionFactory;

    /**
     * @param ScheduleCollectionFactory $scheduleCollectionFactory
     */
    public function __construct(
        ScheduleCollectionFactory $scheduleCollectionFactory
    ) {
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
    }

    /**
     * Get cron status list for athoscommerce_task_execution
     *
     * @param string $status
     * @param int $currentPage
     * @param int $pageSize
     * @return array
     */
    public function getList(string $status = '', int $currentPage = 1, int $pageSize = 20): array
    {
        $collection = $this->scheduleCollectionFactory->create();

        // Filter by job code
        $collection->addFieldToFilter('job_code', 'athoscommerce_task_execution');

        // Apply status filter if provided
        if (!empty($status)) {
            $collection->addFieldToFilter('status', $status);
        }

        // Default ordering - latest first
        $collection->setOrder('scheduled_at', 'DESC');

        // Get total count before applying pagination
        $totalCount = $collection->getSize();

        // Apply pagination
        $collection->setPageSize($pageSize);
        $collection->setCurPage($currentPage);

        $cronStatusItems = [];
        /** @var \Magento\Cron\Model\Schedule $scheduleItem */
        foreach ($collection->getItems() as $scheduleItem) {
            $cronStatusItems[] = [
                'schedule_id' => (int)$scheduleItem->getScheduleId(),
                'job_code' => $scheduleItem->getJobCode(),
                'status' => $scheduleItem->getStatus(),
                'messages' => $scheduleItem->getMessages() ?: '',
                'created_at' => $scheduleItem->getCreatedAt(),
                'scheduled_at' => $scheduleItem->getScheduledAt(),
                'executed_at' => $scheduleItem->getExecutedAt() ?: '',
                'finished_at' => $scheduleItem->getFinishedAt() ?: ''
            ];
        }

        return [
            'data' => [
                'cron_jobs' => $cronStatusItems,
                'total' => $totalCount,
                'currentPage' => $currentPage,
                'pageSize' => $pageSize
            ]
        ];
    }
}
