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

namespace AthosCommerce\Feed\Test\Unit\Model\Task;

use Magento\Framework\Lock\LockManagerInterface;
use AthosCommerce\Feed\Model\Task\StoreExecutionLock;

class StoreExecutionLockTest extends \PHPUnit\Framework\TestCase
{
    public function testIsLockedDelegatesToLockManager(): void
    {
        $lockManagerMock = $this->createMock(LockManagerInterface::class);
        $lock = new StoreExecutionLock($lockManagerMock);

        $lockManagerMock->expects($this->once())
            ->method('isLocked')
            ->with('athoscommerce_task_store_37a8eec1ce19687d132fe29051dca629')
            ->willReturn(true);

        $this->assertTrue($lock->isLocked('default'));
    }

    public function testAcquireDelegatesToLockManagerWithNoWait(): void
    {
        $lockManagerMock = $this->createMock(LockManagerInterface::class);
        $lock = new StoreExecutionLock($lockManagerMock);

        $lockManagerMock->expects($this->once())
            ->method('lock')
            ->with('athoscommerce_task_store_37a8eec1ce19687d132fe29051dca629', 0)
            ->willReturn(true);

        $this->assertTrue($lock->acquire('default'));
    }

    public function testReleaseDelegatesToLockManager(): void
    {
        $lockManagerMock = $this->createMock(LockManagerInterface::class);
        $lock = new StoreExecutionLock($lockManagerMock);

        $lockManagerMock->expects($this->once())
            ->method('unlock')
            ->with('athoscommerce_task_store_37a8eec1ce19687d132fe29051dca629')
            ->willReturn(true);
        $lock->release('default');
    }
}
