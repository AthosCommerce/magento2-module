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

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Api\ExecutePendingTasksInterface;
use AthosCommerce\Feed\Api\ExecuteTaskInterface;
use AthosCommerce\Feed\Api\MetadataInterface;
use AthosCommerce\Feed\Api\StoreExecutionLockInterface;
use AthosCommerce\Feed\Api\TaskRepositoryInterface;
use AthosCommerce\Feed\Model\ResourceModel\Task as TaskResource;
use AthosCommerce\Feed\Model\Task\StoreExecutionLock;

class ExecutePendingTasks implements ExecutePendingTasksInterface
{
    /**
     * @var TaskRepositoryInterface
     */
    private $taskRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var ExecuteTaskInterface
     */
    private $executeTask;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;
    /**
     * @var TaskResource
     */
    private $taskResource;
    /**
     * @var StoreExecutionLockInterface
     */
    private $storeExecutionLock;

    /**
     * ExecutePendingTasks constructor.
     *
     * @param TaskRepositoryInterface $taskRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ExecuteTaskInterface $executeTask
     * @param AthosCommerceLogger $logger
     * @param TaskResource $taskResource
     * @param StoreExecutionLockInterface|null $storeExecutionLock
     */
    public function __construct(
        TaskRepositoryInterface $taskRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ExecuteTaskInterface $executeTask,
        AthosCommerceLogger $logger,
        ?TaskResource $taskResource = null,
        ?StoreExecutionLockInterface $storeExecutionLock = null
    ) {
        $this->taskRepository = $taskRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->executeTask = $executeTask;
        $this->logger = $logger;
        $this->taskResource = $taskResource ?: ObjectManager::getInstance()->get(TaskResource::class);
        $this->storeExecutionLock = $storeExecutionLock ?: ObjectManager::getInstance()->get(
            StoreExecutionLock::class
        );
    }

    /**
     * @param string|null $storeCode
     * @param string $executionMode
     * @return array
     * @throws LocalizedException
     */
    public function execute(
        ?string $storeCode = null,
        string $executionMode = ExecutePendingTasksInterface::EXECUTION_MODE_UNKNOWN
    ): array
    {
        $storeCode = $storeCode !== null ? trim($storeCode) : null;
        if ($storeCode === '') {
            $storeCode = null;
        }
        $executionMode = trim($executionMode) !== '' ? strtolower(trim($executionMode)) : ExecutePendingTasksInterface::EXECUTION_MODE_UNKNOWN;
        if (!in_array($executionMode, [
            ExecutePendingTasksInterface::EXECUTION_MODE_CLI,
            ExecutePendingTasksInterface::EXECUTION_MODE_CRON,
            ExecutePendingTasksInterface::EXECUTION_MODE_UNKNOWN,
        ], true)) {
            $executionMode = ExecutePendingTasksInterface::EXECUTION_MODE_UNKNOWN;
        }
        $this->logger->info(
            'TaskExecution: Pending tasks execution started.',
            ['store' => $storeCode, 'executionMode' => $executionMode]
        );
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(TaskInterface::STATUS, MetadataInterface::TASK_STATUS_PENDING)
            ->create();
        $taskList = $this->taskRepository->getList($searchCriteria);
        $taskItems = $taskList->getItems();
        $this->logger->info('TaskExecution: Total pending tasks count: ' . $taskList->getTotalCount());

        $result = $this->processTasks($taskItems, $storeCode, $executionMode);
        $this->logger->info(
            'TaskExecution: Pending tasks execution completed.',
            ['store' => $storeCode, 'executionMode' => $executionMode]
        );

        return $result;
    }

    /**
     * @param string $storeCode
     * @param string $executionMode
     * @return array
     * @throws LocalizedException
     */
    public function executeForStoreWorker(
        string $storeCode,
        string $executionMode = ExecutePendingTasksInterface::EXECUTION_MODE_UNKNOWN
    ): array {
        $storeCode = trim($storeCode);
        if ($storeCode === '') {
            return [];
        }

        if (!$this->storeExecutionLock->acquire($storeCode)) {
            $this->logger->info(
                'TaskExecution: Store worker skipped because another worker already holds the store lock.',
                ['store' => $storeCode, 'executionMode' => $executionMode]
            );
            return [];
        }

        try {
            $claimedTaskIds = $this->taskResource->claimPendingTasksForStore($storeCode);
            if ($claimedTaskIds === []) {
                return [];
            }

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(TaskInterface::ENTITY_ID, $claimedTaskIds, 'in')
                ->create();
            $taskItems = $this->taskRepository->getList($searchCriteria)->getItems();
            $taskMap = [];
            foreach ($taskItems as $taskItem) {
                $taskMap[(int) $taskItem->getEntityId()] = $taskItem;
            }

            $orderedTasks = [];
            foreach ($claimedTaskIds as $claimedTaskId) {
                if (!isset($taskMap[$claimedTaskId])) {
                    continue;
                }

                $orderedTasks[] = $taskMap[$claimedTaskId];
            }

            return $this->processTasks($orderedTasks, $storeCode, $executionMode, false);
        } finally {
            $this->storeExecutionLock->release($storeCode);
        }
    }

    /**
     * @param TaskInterface $task
     * @param string|null $storeCode
     * @return bool
     */
    private function canProcessTask(TaskInterface $task, ?string $storeCode): bool
    {
        if ($storeCode === null) {
            return true;
        }

        $payload = $task->getPayload();
        if (!isset($payload['store']) || !is_string($payload['store'])) {
            return false;
        }

        return $payload['store'] === $storeCode;
    }

    /**
     * @param TaskInterface[] $taskItems
     * @param string|null $storeCode
     * @param string $executionMode
     * @param bool $filterByStore
     * @return array
     */
    private function processTasks(
        array $taskItems,
        ?string $storeCode,
        string $executionMode,
        bool $filterByStore = true
    ): array {
        $result = [];
        foreach ($taskItems as $task) {
            if ($filterByStore && !$this->canProcessTask($task, $storeCode)) {
                continue;
            }

            $taskId = $task->getEntityId();
            try {
                $this->logger->info(
                    'TaskExecution: Execution started for each task',
                    [
                        'method' => __METHOD__,
                        'taskId' => $taskId,
                        'status' => $task->getStatus(),
                        'store' => $storeCode,
                        'executionMode' => $executionMode,
                    ]
                );
                $result[$taskId] = $this->executeTask->execute($task);
            } catch (\Throwable $exception) {
                $this->logger->error(
                    sprintf('Task ID %d failed. Error: %s', $taskId, $exception->getMessage()),
                    [
                        'taskId' => $taskId,
                        'trace' => $exception->getTraceAsString(),
                        'store' => $storeCode,
                        'executionMode' => $executionMode,
                    ]
                );
                $result[$taskId] = 'ERROR';
            }
        }

        return $result;
    }
}
