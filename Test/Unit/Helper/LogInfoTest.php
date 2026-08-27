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

namespace AthosCommerce\Feed\Test\Unit\Helper;

use AthosCommerce\Feed\Helper\LogInfo;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Service\Log\LogFileReader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LogInfoTest extends TestCase
{
    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var File|MockObject
     */
    private $fileDriverMock;

    /**
     * @var AthosCommerceLogger|MockObject
     */
    private $loggerMock;

    /**
     * @var LogInfo
     */
    private $helper;

    private string $logDirPath;
    private string $logFilePath;

    protected function setUp(): void
    {
        $this->logDirPath = sys_get_temp_dir() . '/athos-loginfo-test-' . uniqid('', true);
        if (!is_dir($this->logDirPath)) {
            mkdir($this->logDirPath, 0777, true);
        }
        $this->logFilePath = $this->logDirPath . '/athoscommerce_feed.log';

        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileDriverMock = $this->createMock(File::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->directoryListMock->method('getPath')
            ->with(DirectoryList::LOG)
            ->willReturn($this->logDirPath);

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

        $this->helper = new LogInfo(
            $this->directoryListMock,
            $this->fileDriverMock,
            $this->loggerMock,
            new LogFileReader($this->fileDriverMock, $this->loggerMock)
        );
    }

    protected function tearDown(): void
    {
        if (is_file($this->logFilePath)) {
            unlink($this->logFilePath);
        }
        if (is_dir($this->logDirPath)) {
            rmdir($this->logDirPath);
        }
    }

    public function testDeleteExtensionLogFileSuccess(): void
    {
        $expectedFilePath = $this->logDirPath . '/athoscommerce_feed.log';

        $this->fileDriverMock->expects($this->once())
            ->method('isExists')
            ->with($expectedFilePath)
            ->willReturn(true);

        $this->fileDriverMock->expects($this->once())
            ->method('deleteFile')
            ->with($expectedFilePath);

        $this->loggerMock->expects($this->exactly(2))
            ->method('info');

        $result = $this->helper->deleteExtensionLogFile();
        $this->assertTrue($result);
    }

    public function testDeleteExtensionLogFileNotFound(): void
    {
        $expectedFilePath = $this->logDirPath . '/athoscommerce_feed.log';

        $this->fileDriverMock->expects($this->once())
            ->method('isExists')
            ->with($expectedFilePath)
            ->willReturn(false);

        $this->fileDriverMock->expects($this->never())
            ->method('deleteFile');

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains(LogInfo::LOG['deleteExtensionLogFileError']));

        $result = $this->helper->deleteExtensionLogFile();
        $this->assertFalse($result);
    }

    public function testGetExtensionLogFileUncompressed(): void
    {
        $expectedFilePath = $this->logDirPath . '/athoscommerce_feed.log';
        $logContent = 'sample log content';
        file_put_contents($expectedFilePath, $logContent);

        $this->fileDriverMock->expects($this->once())
            ->method('isExists')
            ->with($expectedFilePath)
            ->willReturn(true);

        $result = $this->helper->getExtensionLogFile(false);
        $this->assertEquals($logContent, $result);
    }

    public function testGetExtensionLogFileCompressed(): void
    {
        $expectedFilePath = $this->logDirPath . '/athoscommerce_feed.log';
        $logContent = 'sample log content to compress';
        file_put_contents($expectedFilePath, $logContent);

        $this->fileDriverMock->expects($this->once())
            ->method('isExists')
            ->with($expectedFilePath)
            ->willReturn(true);

        $result = $this->helper->getExtensionLogFile(true);

        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        $expectedCompressed = rtrim(strtr(base64_encode(gzdeflate($logContent, 9)), '+/', '-_'), '=');

        $this->assertEquals($expectedCompressed, $result);
    }

    public function testGetLogFileHandlesFileSystemException(): void
    {
        $expectedFilePath = $this->logDirPath . '/athoscommerce_feed.log';

        $this->fileDriverMock->expects($this->once())
            ->method('isExists')
            ->with($expectedFilePath)
            ->willThrowException(new FileSystemException(__('File error')));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error fetching log file:'));

        $result = $this->helper->getExtensionLogFile(false);
        $this->assertEquals('', $result);
    }

    /**
     * Test keyword filtering with plain text search
     */
    public function testGetExtensionLogFileWithPlainKeyword(): void
    {
        $expectedFilePath = $this->logDirPath . '/athoscommerce_feed.log';
        $logContent = "Line 1: INFO operation succeeded\nLine 2: ERROR system failure\nLine 3: DEBUG info";
        file_put_contents($expectedFilePath, $logContent);

        $this->fileDriverMock->method('isExists')->willReturn(true);

        $result = $this->helper->getExtensionLogFile(false, 100, 0, 0, 'ERROR');

        $this->assertEquals('Line 2: ERROR system failure', $result);
    }

    /**
     * Test keyword filtering treats regex-like input as plain substring
     */
    public function testGetExtensionLogFileWithRegexLikeKeywordAsLiteral(): void
    {
        $expectedFilePath = $this->logDirPath . '/athoscommerce_feed.log';
        $logContent = "Line 1: INFO success\nLine 2: /(ERROR|WARNING)/i marker\nLine 3: WARNING soft failure";
        file_put_contents($expectedFilePath, $logContent);

        $this->fileDriverMock->method('isExists')->willReturn(true);

        $result = $this->helper->getExtensionLogFile(false, 100, 0, 0, '/(ERROR|WARNING)/i');

        $this->assertEquals('Line 2: /(ERROR|WARNING)/i marker', $result);
    }

    /**
     * Test keyword filtering with regex-like malformed input works as plain substring
     */
    public function testGetExtensionLogFileWithRegexLikeMalformedLiteralKeyword(): void
    {
        $expectedFilePath = $this->logDirPath . '/athoscommerce_feed.log';
        // Content contains a literal malformed pattern string
        $logContent = "Line 1: Normal log\nLine 2: /[unclosed bracket log";
        file_put_contents($expectedFilePath, $logContent);

        $this->fileDriverMock->method('isExists')->willReturn(true);

        // Regex-looking pattern is treated as plain text.
        $result = $this->helper->getExtensionLogFile(false, 100, 0, 0, '/[unclosed');

        $this->assertEquals('Line 2: /[unclosed bracket log', $result);
    }

    public function testGetExtensionLogFileReturnsOnlyLastLinesWithoutFilters(): void
    {
        $expectedFilePath = $this->logDirPath . '/athoscommerce_feed.log';
        $logContent = "Line 1\nLine 2\nLine 3\nLine 4\nLine 5";
        file_put_contents($expectedFilePath, $logContent);

        $this->fileDriverMock->method('isExists')->willReturn(true);

        $result = $this->helper->getExtensionLogFile(false, 2);

        $this->assertEquals("Line 4\nLine 5", $result);
    }
}