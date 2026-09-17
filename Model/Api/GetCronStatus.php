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
use AthosCommerce\Feed\Api\Data\CronStatusListInterface;
use AthosCommerce\Feed\Api\Data\CronStatusListInterfaceFactory;
use AthosCommerce\Feed\Api\GetCronStatusInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use Magento\Cron\Model\ResourceModel\Schedule\Collection as ScheduleCollection;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory as ScheduleCollectionFactory;
use Magento\Cron\Model\Schedule;

class GetCronStatus implements GetCronStatusInterface
{
    private const JOB_CODE_ALL = 'all';

    private const JOB_CODES = [
        'athoscommerce_task_execution',
        'athoscommerce_live_indexing_discovery',
        'athoscommerce_live_indexing_sync',
    ];

    private const RECENT_RUN_LIMIT = 20;

    /**
     * Maximum allowed limit per page to prevent memory/performance issues.
     */
    private const MAX_PAGE_SIZE = 250;

    /** @var ScheduleCollectionFactory */
    private $scheduleCollectionFactory;

    /**
     * @var CronStatusListInterfaceFactory
     */
    private $responseFactory;

    /**
     * @var CronStatusInterfaceFactory
     */
    private $cronStatusFactory;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param ScheduleCollectionFactory $scheduleCollectionFactory
     * @param CronStatusListInterfaceFactory $responseFactory
     * @param CronStatusInterfaceFactory $cronStatusFactory
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        ScheduleCollectionFactory        $scheduleCollectionFactory,
        CronStatusListInterfaceFactory   $responseFactory,
        CronStatusInterfaceFactory       $cronStatusFactory,
        AthosCommerceLogger              $logger
    ) {
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
        $this->responseFactory = $responseFactory;
        $this->cronStatusFactory = $cronStatusFactory;
        $this->logger = $logger;
    }

    /**
     * Get the recent AthosCommerce cron status summary.
     *
     * @param string $jobCode
     * @param string $status
     * @param int $currentPage
     * @param int $pageSize
     *
     * @return CronStatusListInterface
     */
    public function getList(
        string $jobCode = self::JOB_CODE_ALL,
        string $status = '',
        int $currentPage = 1,
        int $pageSize = self::RECENT_RUN_LIMIT
    ): CronStatusListInterface {

        if ($pageSize > self::MAX_PAGE_SIZE) {
            $this->logger->error(
                "[CRON STATUS API] Page size exceeds maximum limit.",
                [
                    'requested_page_size' => $pageSize,
                    'max_page_size' => self::MAX_PAGE_SIZE
                ]
            );
        }

        $effectivePageSize = $pageSize > 0 ? $pageSize : self::RECENT_RUN_LIMIT;
        $effectiveCurrentPage = max(1, $currentPage);

        /** @var CronStatusListInterface $response */
        $response = $this->responseFactory->create();
        $jobCodes = $this->resolveJobCodes($jobCode);
        $recentCollection = $this->getBaseCollection($jobCodes, $status);
        $totalRecords = (int) $recentCollection->getSize();
        $recentCollection->setOrder('scheduled_at', ScheduleCollection::SORT_ORDER_DESC);
        $recentCollection->setOrder('schedule_id', ScheduleCollection::SORT_ORDER_DESC);
        $recentCollection->setPageSize($effectivePageSize);
        $recentCollection->setCurPage($effectiveCurrentPage);

        $this->logger->debug(
            "[CRON STATUS API] SQL QueryAfterPagination: ",
            [
                'query' => $recentCollection->getSelect()->__toString()
            ]
        );
        $cronJobs = [];
        $jobItems = $recentCollection->getItems();

        /** @var Schedule $scheduleItem */
        foreach ($jobItems as $scheduleItem) {
            $cronJob = $this->cronStatusFactory->create();
            $cronJob->setScheduleId((int)$scheduleItem->getScheduleId());
            $cronJob->setJobCode((string)$scheduleItem->getJobCode());
            $cronJob->setStatus((string)$scheduleItem->getStatus());
            $cronJob->setMessages((string)($scheduleItem->getMessages() ?: ''));
            $cronJob->setCreatedAt((string)$scheduleItem->getCreatedAt());
            $cronJob->setScheduledAt((string)$scheduleItem->getScheduledAt());
            $cronJob->setExecutedAt((string)($scheduleItem->getExecutedAt() ?: ''));
            $cronJob->setFinishedAt((string)($scheduleItem->getFinishedAt() ?: ''));
            $cronJobs[] = $cronJob;
        }

        return $response
            ->setTotalRecords($totalRecords)
            ->setCronJobs($cronJobs);
    }

    /**
     * Create the base collection for AthosCommerce cron jobs.
     *
     * @param string[] $jobCodes
     * @param string $status
     *
     * @return ScheduleCollection
     */
    private function getBaseCollection(array $jobCodes, string $status = ''): ScheduleCollection
    {
        $collection = $this->scheduleCollectionFactory->create();
        $collection->addFieldToFilter(
            'job_code',
            array_map(
                static function (string $jobCode): array {
                    return ['eq' => $jobCode];
                },
                $jobCodes
            )
        );
        if ($status !== '') {
            $collection->addFieldToFilter('status', $status);
        }

        $this->logger->debug(
            "[CRON STATUS API] Collection SQL Query: ",
            [
                'query' => $collection->getSelect()->__toString()
            ]
        );

        return $collection;
    }

    /**
     * Resolve the requested job codes.
     *
     * @param string $jobCode
     *
     * @return string[]
     */
    private function resolveJobCodes(string $jobCode): array
    {
        if ($jobCode === self::JOB_CODE_ALL || $jobCode === '') {
            return self::JOB_CODES;
        }

        if (in_array($jobCode, self::JOB_CODES, true)) {
            return [$jobCode];
        }

        return self::JOB_CODES;
    }
}
