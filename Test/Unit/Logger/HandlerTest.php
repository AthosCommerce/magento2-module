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

namespace AthosCommerce\Feed\Test\Unit\Logger;

use AthosCommerce\Feed\Logger\Handler;
use Magento\Framework\Filesystem\Driver\File;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class HandlerTest extends TestCase
{
    /**
     * @var string[]
     */
    private array $filesToDelete = [];

    public function testHandleWritesInfoRecords(): void
    {
        $handler = $this->createHandler();
        $record = $this->createRecord(Logger::INFO, 'info message');

        $this->assertTrue($handler->isHandling($record));
        $this->assertFalse($handler->handle($record));
        $handler->_resetState();

        $this->assertStringContainsString('info message', $this->readLogFile($handler));
    }

    public function testHandleSkipsDebugRecords(): void
    {
        $handler = $this->createHandler();
        $record = $this->createRecord(Logger::DEBUG, 'debug message');

        $this->assertFalse($handler->handle($record));
        $handler->_resetState();
        $this->assertSame('', $this->readLogFile($handler));
    }

    public function testHandleSkipsErrorRecords(): void
    {
        $handler = $this->createHandler();
        $record = $this->createRecord(Logger::ERROR, 'error message');

        $this->assertFalse($handler->handle($record));
        $handler->_resetState();
        $this->assertSame('', $this->readLogFile($handler));
    }

    protected function tearDown(): void
    {
        foreach ($this->filesToDelete as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    private function createHandler(): Handler
    {
        $fileName = 'athos-handler-' . uniqid('', true) . '.log';
        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR;
        $fullPath = $filePath . $fileName;
        $this->filesToDelete[] = $fullPath;

        return new Handler(new File(), $filePath, $fileName);
    }

    /**
     * @param int $level
     * @param string $message
     * @return array|\Monolog\LogRecord
     */
    private function createRecord(int $level, string $message)
    {
        if (class_exists(\Monolog\LogRecord::class)) {
            return new \Monolog\LogRecord(
                new \DateTimeImmutable(),
                'athos-test',
                Logger::toMonologLevel($level),
                $message
            );
        }

        return [
            'message' => $message,
            'context' => [],
            'level' => $level,
            'level_name' => Logger::getLevelName($level),
            'channel' => 'athos-test',
            'datetime' => new \DateTimeImmutable(),
            'extra' => [],
        ];
    }

    private function readLogFile(Handler $handler): string
    {
        $debugInfo = $handler->__debugInfo();
        $fileName = $debugInfo['fileName'] ?? '';
        $fullPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . ltrim((string)$fileName, DIRECTORY_SEPARATOR);

        if (!is_file($fullPath)) {
            return '';
        }

        return (string)file_get_contents($fullPath);
    }
}
