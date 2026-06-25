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

namespace AthosCommerce\Feed\Model\Task;

use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Api\MetadataInterface;
use AthosCommerce\Feed\Api\TaskRepositoryInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SortOrderBuilderFactory;

class TaskPayloadProvider
{
    /**
     * @var TaskRepositoryInterface
     */
    private $taskRepository;
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;
    /**
     * @var SortOrderBuilderFactory
     */
    private $sortOrderBuilderFactory;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param TaskRepositoryInterface $taskRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param SortOrderBuilderFactory $sortOrderBuilderFactory
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        TaskRepositoryInterface      $taskRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        SortOrderBuilderFactory      $sortOrderBuilderFactory,
        AthosCommerceLogger          $logger
    )
    {
        $this->taskRepository = $taskRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->sortOrderBuilderFactory = $sortOrderBuilderFactory;
        $this->logger = $logger;
    }

    /**
     * @param string $taskType
     * @param bool $preferSuccessful
     * @return array
     */
    public function getLatestPayloadByType(
        string $taskType,
        bool   $preferSuccessful = true
    ): array
    {
        try {
            if ($preferSuccessful) {
                $task = $this->getLatestTaskByTypeAndStatus(
                    $taskType,
                    MetadataInterface::TASK_STATUS_SUCCESS
                );
                if ($task && !empty($task->getPayload())) {
                    return $task->getPayload();
                }
            }

            $task = $this->getLatestTaskByType($taskType);
            if ($task && is_array($task->getPayload())) {
                return $task->getPayload();
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Task PayloadProvider: Failed to load latest task payload',
                [
                    'method' => __METHOD__,
                    'task_type' => $taskType,
                    'prefer_successful' => $preferSuccessful,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            );
        }

        return [];
    }

    /**
     * @param string $taskType
     * @return TaskInterface|null
     */
    public function getLatestTaskByType(string $taskType): ?TaskInterface
    {
        $sortOrder = $this->sortOrderBuilderFactory->create()
            ->setField(TaskInterface::ENTITY_ID)
            ->setDescendingDirection()
            ->create();

        $searchCriteria = $this->searchCriteriaBuilderFactory->create()
            ->addFilter(TaskInterface::TYPE, $taskType)
            ->addSortOrder($sortOrder)
            ->setPageSize(1)
            ->setCurrentPage(1)
            ->create();

        $items = $this->taskRepository->getList($searchCriteria)->getItems();
        $task = reset($items);

        return $task instanceof TaskInterface ? $task : null;
    }

    /**
     * @param string $taskType
     * @param string $status
     * @return TaskInterface|null
     */
    public function getLatestTaskByTypeAndStatus(
        string $taskType,
        string $status
    ): ?TaskInterface
    {
        $sortOrder = $this->sortOrderBuilderFactory->create()
            ->setField(TaskInterface::ENTITY_ID)
            ->setDescendingDirection()
            ->create();

        $searchCriteria = $this->searchCriteriaBuilderFactory->create()
            ->addFilter(TaskInterface::TYPE, $taskType)
            ->addFilter(TaskInterface::STATUS, $status)
            ->addSortOrder($sortOrder)
            ->setPageSize(1)
            ->setCurrentPage(1)
            ->create();

        $items = $this->taskRepository->getList($searchCriteria)->getItems();
        $task = reset($items);

        return $task instanceof TaskInterface ? $task : null;
    }
}
