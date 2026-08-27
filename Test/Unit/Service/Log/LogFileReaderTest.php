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

namespace AthosCommerce\Feed\Test\Unit\Service\Log;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Service\Log\LogFileReader;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LogFileReaderTest extends TestCase
{
    /**
     * @var File|MockObject
     */
    private $fileDriverMock;

    /**
     * @var AthosCommerceLogger|MockObject
     */
    private $loggerMock;

    /**
     * @var LogFileReader
     */
    private $reader;

    /**
     * @var string
     */
    private $logFilePath;

    protected function setUp(): void
    {
        $logDir = sys_get_temp_dir() . '/athos-log-reader-test-' . uniqid('', true);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $this->logFilePath = $logDir . '/athoscommerce_feed.log';

        $this->fileDriverMock = $this->createMock(File::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->fileDriverMock->method('fileOpen')
            ->willReturnCallback(static function (string $path, string $mode) {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                return fopen($path, $mode);
            });
        $this->fileDriverMock->method('endOfFile')
            ->willReturnCallback(static function ($resource): bool {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                return feof($resource);
            });
        $this->fileDriverMock->method('fileReadLine')
            ->willReturnCallback(static function ($resource, int $length, ?string $ending = null): string {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                $line = stream_get_line($resource, $length, $ending ?? "\n");
                return $line === false ? '' : $line;
            });
        $this->fileDriverMock->method('fileClose')
            ->willReturnCallback(static function ($resource): bool {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                return fclose($resource);
            });
        $this->fileDriverMock->method('fileSeek')
            ->willReturnCallback(static function ($resource, int $offset, int $whence = SEEK_SET): int {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                return fseek($resource, $offset, $whence);
            });
        $this->fileDriverMock->method('fileTell')
            ->willReturnCallback(static function ($resource): int {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                $position = ftell($resource);
                return $position === false ? 0 : $position;
            });
        $this->fileDriverMock->method('fileRead')
            ->willReturnCallback(static function ($resource, int $length): string {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                $content = fread($resource, $length);
                return $content === false ? '' : $content;
            });

        $this->reader = new LogFileReader($this->fileDriverMock, $this->loggerMock);
    }

    protected function tearDown(): void
    {
        if (is_file($this->logFilePath)) {
            unlink($this->logFilePath);
        }
        $logDir = dirname($this->logFilePath);
        if (is_dir($logDir)) {
            rmdir($logDir);
        }
    }

    public function testReadReturnsLastLinesWithoutFilters(): void
    {
        $content = "Line 1\nLine 2\nLine 3\nLine 4\nLine 5\n";
        file_put_contents($this->logFilePath, $content);

        $result = $this->reader->read($this->logFilePath, false, 2);

        $this->assertSame("Line 4\nLine 5", $result);
    }

    public function testReadAppliesDateAndKeywordFilter(): void
    {
        $content = implode("\n", [
            '[2025-01-15T10:00:00+00:00] INFO start',
            '[2025-01-15T11:00:00+00:00] ERROR boom',
            '[2025-01-16T12:00:00+00:00] ERROR next day',
        ]);
        file_put_contents($this->logFilePath, $content);

        $result = $this->reader->read(
            $this->logFilePath,
            false,
            100,
            0,
            0,
            'ERROR',
            '2025-01-15',
            '2025-01-15'
        );

        $this->assertSame('[2025-01-15T11:00:00+00:00] ERROR boom', $result);
    }

    public function testReadCompressesOutputWhenRequested(): void
    {
        $content = 'sample log content';
        file_put_contents($this->logFilePath, $content);

        $result = $this->reader->read($this->logFilePath, true, 100);

        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        $expected = rtrim(strtr(base64_encode(gzdeflate($content, 9)), '+/', '-_'), '=');
        $this->assertSame($expected, $result);
    }

    public function testReadReturnsEmptyAndLogsErrorOnFilesystemException(): void
    {
        $this->fileDriverMock = $this->createMock(File::class);
        $this->fileDriverMock->method('fileOpen')
            ->willThrowException(new FileSystemException(__('Cannot open file')));
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error fetching log file:'));
        $this->reader = new LogFileReader($this->fileDriverMock, $this->loggerMock);

        $result = $this->reader->read($this->logFilePath);

        $this->assertSame('', $result);
    }
}

