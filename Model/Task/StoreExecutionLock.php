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

use AthosCommerce\Feed\Api\StoreExecutionLockInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Lock\LockManagerInterface;

class StoreExecutionLock implements StoreExecutionLockInterface
{
    private const LOCK_NAME_PREFIX = 'athoscommerce_task_store_';

    /**
     * @var LockManagerInterface
     */
    private $lockManager;

    /**
     * @param LockManagerInterface|null $lockManager
     */
    public function __construct(?LockManagerInterface $lockManager = null)
    {
        $this->lockManager = $lockManager ?: ObjectManager::getInstance()->get(LockManagerInterface::class);
    }

    /**
     * @inheritDoc
     */
    public function isLocked(string $storeCode): bool
    {
        return $this->lockManager->isLocked($this->getLockName($storeCode));
    }

    /**
     * @inheritDoc
     */
    public function acquire(string $storeCode): bool
    {
        return $this->lockManager->lock($this->getLockName($storeCode), 0);
    }

    /**
     * @inheritDoc
     */
    public function release(string $storeCode): void
    {
        $this->lockManager->unlock($this->getLockName($storeCode));
    }

    /**
     * @param string $storeCode
     * @return string
     */
    private function getLockName(string $storeCode): string
    {
        return self::LOCK_NAME_PREFIX . substr(hash('sha256', trim($storeCode)), 0, 32);
    }
}
