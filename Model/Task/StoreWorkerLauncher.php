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

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ShellFactory;
use AthosCommerce\Feed\Api\StoreExecutionLockInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\ResourceModel\Task as TaskResource;

class StoreWorkerLauncher
{
    public const WORKER_COMMAND_NAME = 'athoscommerce:task:execute-pending-for-store';

    /**
     * @var TaskResource
     */
    private $taskResource;

    /**
     * @var ShellFactory
     */
    private $shellFactory;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;
    /**
     * @var StoreExecutionLockInterface
     */
    private $storeExecutionLock;

    /**
     * @param TaskResource $taskResource
     * @param ShellFactory $shellFactory
     * @param AthosCommerceLogger $logger
     * @param StoreExecutionLockInterface $storeExecutionLock
     */
    public function __construct(
        TaskResource $taskResource,
        ShellFactory $shellFactory,
        AthosCommerceLogger $logger,
        ?StoreExecutionLockInterface $storeExecutionLock = null
    ) {
        $this->taskResource = $taskResource;
        $this->shellFactory = $shellFactory;
        $this->logger = $logger;
        $this->storeExecutionLock = $storeExecutionLock ?: ObjectManager::getInstance()->get(StoreExecutionLock::class);
    }

    /**
     * @param string|null $storeCode
     * @return string[]
     */
    public function spawnPendingStoreWorkers(?string $storeCode = null): array
    {
        $spawnedStoreCodes = [];
        foreach ($this->getPendingStoreCodes($storeCode) as $pendingStoreCode) {
            if ($this->storeExecutionLock->isLocked($pendingStoreCode)) {
                $this->logger->info(
                    'Skipping worker spawn because store execution is already locked.',
                    ['store' => $pendingStoreCode]
                );
                continue;
            }

            try {
                $this->shellFactory->create()->execute(
                    'cd %s && %s %s ' . self::WORKER_COMMAND_NAME . ' --store=%s > /dev/null 2>&1 &',
                    [BP, PHP_BINARY, 'bin/magento', $pendingStoreCode]
                );
                $spawnedStoreCodes[] = $pendingStoreCode;
            } catch (\Throwable $exception) {
                $this->logger->error(
                    'Failed to spawn pending task worker.',
                    [
                        'store' => $pendingStoreCode,
                        'message' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString(),
                    ]
                );
            }
        }

        return $spawnedStoreCodes;
    }

    /**
     * @param string|null $storeCode
     * @return string[]
     */
    public function getPendingStoreCodes(?string $storeCode = null): array
    {
        return $this->taskResource->getPendingStoreCodes($storeCode);
    }
}
