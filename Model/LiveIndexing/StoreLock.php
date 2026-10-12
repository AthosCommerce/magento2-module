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

namespace AthosCommerce\Feed\Model\LiveIndexing;

use Magento\Framework\Lock\LockManagerInterface;

/**
 * Per store view lock for a live indexing job (discovery or entity sync).
 *
 * Held for the whole run of one store, whoever started it (cron worker or CLI), so the same job
 * never processes the same store twice at once. Discovery and sync use separate locks.
 */
class StoreLock
{
    public const TYPE_DISCOVERY = 'discovery';
    public const TYPE_SYNC = 'sync';

    private const LOCK_NAME_PREFIX = 'athoscommerce_live_indexing_';

    /**
     * @var LockManagerInterface
     */
    private $lockManager;

    /**
     * @param LockManagerInterface $lockManager
     */
    public function __construct(LockManagerInterface $lockManager)
    {
        $this->lockManager = $lockManager;
    }

    /**
     * Whether the job is currently running for the store.
     *
     * @param string $type
     * @param string $storeCode
     * @return bool
     */
    public function isLocked(string $type, string $storeCode): bool
    {
        return $this->lockManager->isLocked($this->getLockName($type, $storeCode));
    }

    /**
     * Take the lock without waiting; false when the job already runs for the store.
     *
     * @param string $type
     * @param string $storeCode
     * @return bool
     */
    public function acquire(string $type, string $storeCode): bool
    {
        return $this->lockManager->lock($this->getLockName($type, $storeCode), 0);
    }

    /**
     * Release the lock taken by acquire().
     *
     * @param string $type
     * @param string $storeCode
     * @return void
     */
    public function release(string $type, string $storeCode): void
    {
        $this->lockManager->unlock($this->getLockName($type, $storeCode));
    }

    /**
     * Lock name: prefix, job type and a hash of the store code (lock names have a length limit).
     *
     * @param string $type
     * @param string $storeCode
     * @return string
     */
    private function getLockName(string $type, string $storeCode): string
    {
        return self::LOCK_NAME_PREFIX . $type . '_' . substr(hash('sha256', trim($storeCode)), 0, 32);
    }
}
