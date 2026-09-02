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

use AthosCommerce\Feed\Logger\VerboseHandler;
use AthosCommerce\Feed\Model\Config as ConfigModel;
use Magento\Framework\Filesystem\Driver\File;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VerboseHandlerTest extends TestCase
{
    /**
     * @var string[]
     */
    private array $filesToDelete = [];

    public function testIsHandlingReturnsFalseWhenDebugIsDisabled(): void
    {
        $handler = $this->createHandler(false);

        $this->assertFalse($handler->isHandling($this->createRecord(Level::Debug, 'debug message')));
    }

    public function testHandleWritesDebugRecordsWhenDebugIsEnabled(): void
    {
        $handler = $this->createHandler(true);
        $record = $this->createRecord(Level::Debug, 'debug message');

        $this->assertTrue($handler->isHandling($record));
        $this->assertFalse($handler->handle($record));
        $handler->_resetState();

        $this->assertStringContainsString('debug message', $this->readLogFile($handler));
    }

    public function testHandleSkipsInfoRecordsEvenWhenDebugIsEnabled(): void
    {
        $handler = $this->createHandler(true);
        $record = $this->createRecord(Level::Info, 'info message');

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

    private function createHandler(bool $isDebugEnabled): VerboseHandler
    {
        /** @var ConfigModel&MockObject $configModel */
        $configModel = $this->createMock(ConfigModel::class);
        $configModel->method('isDebugLogEnabled')->willReturn($isDebugEnabled);

        $fileName = 'athos-verbose-handler-' . uniqid('', true) . '.log';
        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR;
        $fullPath = $filePath . $fileName;
        $this->filesToDelete[] = $fullPath;

        return new VerboseHandler($configModel, new File(), $filePath, $fileName);
    }

    private function createRecord(Level $level, string $message): LogRecord
    {
        return new LogRecord(
            new \DateTimeImmutable(),
            'athos-test',
            $level,
            $message
        );
    }

    private function readLogFile(VerboseHandler $handler): string
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
