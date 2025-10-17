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

namespace AthosCommerce\Feed\Test\Unit\Cron;

use AthosCommerce\Feed\Api\ExecutePendingTasksInterface;
use AthosCommerce\Feed\Cron\ExecuteTasks;

class ExecuteTasksTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ExecutePendingTasksInterface
     */
    private $executePendingTaskInterfaceMock;

    private $executeTasks;

    public function setUp(): void
    {
        $this->executePendingTaskInterfaceMock = $this->createMock(ExecutePendingTasksInterface::class);
        $this->executeTasks = new ExecuteTasks($this->executePendingTaskInterfaceMock);
    }

    public function testExecute()
    {
        $this->executePendingTaskInterfaceMock->expects($this->once())
            ->method('execute')
            ->willReturn([]);

        $this->assertSame(null, $this->executeTasks->execute());
    }
}
