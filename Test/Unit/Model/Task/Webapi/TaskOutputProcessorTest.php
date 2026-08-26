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

namespace AthosCommerce\Feed\Test\Unit\Model\Task\Webapi;

use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Model\Task\Webapi\TaskOutputProcessor;
use PHPUnit\Framework\TestCase;

class TaskOutputProcessorTest extends TestCase
{
    public function testExecuteRedactsSensitiveUrlsInPayload(): void
    {
        $task = $this->createMock(TaskInterface::class);
        $task->method('getPayload')->willReturn([
            'secretKey' => 'top-secret',
            'preSignedUrl' => 'https://s3.example.com/file?X-Amz-Signature=abc123',
            'catalogPreSignedUrl' => 'https://s3.example.com/catalog?X-Amz-Signature=def456',
            'store' => 'default',
            'feedSpecification' => [
                'secretKey' => 'nested-secret',
                'preSignedUrl' => 'https://s3.example.com/nested?X-Amz-Signature=ghi789',
                'catalogPreSignedUrl' => 'https://s3.example.com/nested-catalog?X-Amz-Signature=jkl012',
                'format' => 'json',
            ],
        ]);

        $processor = new TaskOutputProcessor();
        $result = $processor->execute($task, []);

        $this->assertSame('****', $result[TaskInterface::PAYLOAD]['secretKey']);
        $this->assertSame('****', $result[TaskInterface::PAYLOAD]['preSignedUrl']);
        $this->assertSame('****', $result[TaskInterface::PAYLOAD]['catalogPreSignedUrl']);
        $this->assertSame('****', $result[TaskInterface::PAYLOAD]['feedSpecification']['secretKey']);
        $this->assertSame('****', $result[TaskInterface::PAYLOAD]['feedSpecification']['preSignedUrl']);
        $this->assertSame('****', $result[TaskInterface::PAYLOAD]['feedSpecification']['catalogPreSignedUrl']);
        $this->assertSame('json', $result[TaskInterface::PAYLOAD]['feedSpecification']['format']);
        $this->assertSame('default', $result[TaskInterface::PAYLOAD]['store']);
    }
}
