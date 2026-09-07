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

namespace AthosCommerce\Feed\Cron;

use Magento\Framework\App\ObjectManager;
use AthosCommerce\Feed\Api\ExecutePendingTasksInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Metric\CollectorInterface;
use AthosCommerce\Feed\Model\Metric\Output\LogOutput;
use AthosCommerce\Feed\Model\Task\StoreWorkerLauncher;
use AthosCommerce\Feed\Service\Config;

class ExecuteTasksCron
{
    /**
     * @var ExecutePendingTasksInterface
     */
    private $executePendingTasks;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var StoreWorkerLauncher
     */
    private $storeWorkerLauncher;
    /**
     * @var LogOutput
     */
    private $logOutput;
    /**
     * @var CollectorInterface
     */
    private $metricCollector;

    /**
     * @param ExecutePendingTasksInterface $executePendingTasks
     * @param AthosCommerceLogger $logger
     * @param Config $config
     * @param StoreWorkerLauncher $storeWorkerLauncher
     * @param LogOutput $logOutput
     * @param CollectorInterface $metricCollector
     */
    public function __construct(
        ExecutePendingTasksInterface $executePendingTasks,
        AthosCommerceLogger $logger,
        ?Config $config = null,
        ?StoreWorkerLauncher $storeWorkerLauncher = null,
        ?LogOutput $logOutput = null,
        ?CollectorInterface $metricCollector = null
    ) {
        $this->executePendingTasks = $executePendingTasks;
        $this->logger = $logger;
        $this->config = $config ?: ObjectManager::getInstance()->get(Config::class);
        $this->storeWorkerLauncher = $storeWorkerLauncher ?: ObjectManager::getInstance()->get(
            StoreWorkerLauncher::class
        );
        $this->logOutput = $logOutput ?: ObjectManager::getInstance()->get(LogOutput::class);
        $this->metricCollector = $metricCollector ?: ObjectManager::getInstance()->get(CollectorInterface::class);
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $this->logger->info('CRON started for task execution');
        $this->metricCollector->setOutput($this->logOutput);
        $this->metricCollector->reset(CollectorInterface::CODE_TASK_EXECUTION);

        $parallelEnabled = $this->config->isParallelCronEnabled();
        $this->collectMetrics('Cron Start', [
            'execution_mode' => ExecutePendingTasksInterface::EXECUTION_MODE_CRON,
            'parallel_enabled' => $parallelEnabled ? '1' : '0',
        ]);

        try {
            if ($parallelEnabled) {
                $pendingStoreCount = count($this->storeWorkerLauncher->getPendingStoreCodes());
                $spawnedStores = $this->storeWorkerLauncher->spawnPendingStoreWorkers();
                $this->collectMetrics('Cron Parallel Dispatch', [
                    'execution_mode' => ExecutePendingTasksInterface::EXECUTION_MODE_CRON,
                    'parallel_enabled' => '1',
                    'pending_store_count' => $pendingStoreCount,
                    'spawned_store_count' => count($spawnedStores),
                ]);
                $this->metricCollector->print(
                    CollectorInterface::CODE_TASK_EXECUTION,
                    CollectorInterface::PRINT_TYPE_FULL
                );
                $this->logger->info(
                    'CRON completed for task execution in parallel mode',
                    ['spawnedStores' => $spawnedStores]
                );
                return;
            }

            $result = $this->executePendingTasks->execute(
                null,
                ExecutePendingTasksInterface::EXECUTION_MODE_CRON
            );
            $this->collectMetrics('Cron Sequential Execution', [
                'execution_mode' => ExecutePendingTasksInterface::EXECUTION_MODE_CRON,
                'parallel_enabled' => '0',
                'processed_task_count' => count($result),
            ]);
            $this->metricCollector->print(
                CollectorInterface::CODE_TASK_EXECUTION,
                CollectorInterface::PRINT_TYPE_FULL
            );
            $this->logger->info('CRON completed for task execution');
        } finally {
            $this->metricCollector->reset(CollectorInterface::CODE_TASK_EXECUTION);
        }
    }

    /**
     * @param string $title
     * @param array $data
     * @return void
     */
    private function collectMetrics(string $title, array $data = []): void
    {
        $this->metricCollector->collect(CollectorInterface::CODE_TASK_EXECUTION, $title, $data);
    }
}
