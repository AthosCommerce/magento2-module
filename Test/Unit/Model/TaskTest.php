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

namespace AthosCommerce\Feed\Test\Unit\Model;

use AthosCommerce\Feed\Api\Data\TaskErrorInterface;
use AthosCommerce\Feed\Api\Data\TaskExtensionInterface;
use AthosCommerce\Feed\Model\Task;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    /**
     * @var Task|MockObject
     */
    private $task;

    protected function setUp(): void
    {
        $this->task = $this->getMockBuilder(Task::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }

    public function testGetAndSetEntityId(): void
    {
        $this->assertSame($this->task, $this->task->setEntityId(10));
        $this->assertSame(10, $this->task->getEntityId());
    }

    public function testGetEntityIdReturnsNullWhenNotSet(): void
    {
        $this->assertNull($this->task->getEntityId());
    }

    public function testGetAndSetType(): void
    {
        $this->assertSame($this->task, $this->task->setType('feed_generation'));
        $this->assertSame('feed_generation', $this->task->getType());
    }

    public function testGetAndSetStatus(): void
    {
        $this->assertSame($this->task, $this->task->setStatus('pending'));
        $this->assertSame('pending', $this->task->getStatus());
    }

    public function testGetPayloadReturnsArrayWhenAlreadySetAsArray(): void
    {
        $payload = ['store' => 'default', 'format' => 'json'];

        $this->assertSame($this->task, $this->task->setPayload($payload));
        $this->assertSame($payload, $this->task->getPayload());
    }

    public function testGetPayloadReturnsEmptyArrayWhenNotSet(): void
    {
        $this->assertSame([], $this->task->getPayload());
    }

    public function testGetAndSetCreatedAt(): void
    {
        $this->assertSame($this->task, $this->task->setCreatedAt('2026-07-06 12:00:00'));
        $this->assertSame('2026-07-06 12:00:00', $this->task->getCreatedAt());
    }

    public function testGetAndSetStartedAt(): void
    {
        $this->assertSame($this->task, $this->task->setStartedAt('2026-07-06 12:01:00'));
        $this->assertSame('2026-07-06 12:01:00', $this->task->getStartedAt());
    }

    public function testGetAndSetEndedAt(): void
    {
        $this->assertSame($this->task, $this->task->setEndedAt('2026-07-06 12:02:00'));
        $this->assertSame('2026-07-06 12:02:00', $this->task->getEndedAt());
    }

    public function testGetAndSetError(): void
    {
        $errorMock = $this->createMock(TaskErrorInterface::class);

        $this->assertSame($this->task, $this->task->setError($errorMock));
        $this->assertSame($errorMock, $this->task->getError());
    }

    public function testGetAndSetExtensionAttributes(): void
    {
        $extensionAttributesMock = $this->createMock(TaskExtensionInterface::class);

        $this->assertSame($this->task, $this->task->setExtensionAttributes($extensionAttributesMock));
        $this->assertSame($extensionAttributesMock, $this->task->getExtensionAttributes());
    }

    public function testGetAndSetProductCount(): void
    {
        $this->assertSame($this->task, $this->task->setProductCount(25));
        $this->assertSame(25, $this->task->getProductCount());
    }

    public function testGetFileSizeReturnsZeroWhenNull(): void
    {
        $this->task->setData(Task::File_Size, null);

        $this->assertSame(0, $this->task->getFileSize());
    }

    public function testGetFileSizeReturnsZeroWhenEmptyString(): void
    {
        $this->task->setData(Task::File_Size, '');

        $this->assertSame(0, $this->task->getFileSize());
    }

    public function testGetAndSetFileSize(): void
    {
        $this->assertSame($this->task, $this->task->setFileSize(2048));
        $this->assertSame(2048, $this->task->getFileSize());
    }
}
