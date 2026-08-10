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

    private string $logDirPath = '/var/log';

    protected function setUp(): void
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileDriverMock = $this->createMock(File::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->directoryListMock->method('getPath')
            ->with(DirectoryList::LOG)
            ->willReturn($this->logDirPath);

        $this->helper = new LogInfo(
            $this->directoryListMock,
            $this->fileDriverMock,
            $this->loggerMock
        );
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

        $this->fileDriverMock->expects($this->once())
            ->method('isExists')
            ->with($expectedFilePath)
            ->willReturn(true);

        $this->fileDriverMock->expects($this->once())
            ->method('fileGetContents')
            ->with($expectedFilePath)
            ->willReturn($logContent);

        $result = $this->helper->getExtensionLogFile(false);
        $this->assertEquals($logContent, $result);
    }

    public function testGetExtensionLogFileCompressed(): void
    {
        $expectedFilePath = $this->logDirPath . '/athoscommerce_feed.log';
        $logContent = 'sample log content to compress';

        $this->fileDriverMock->expects($this->once())
            ->method('isExists')
            ->with($expectedFilePath)
            ->willReturn(true);

        $this->fileDriverMock->expects($this->once())
            ->method('fileGetContents')
            ->with($expectedFilePath)
            ->willReturn($logContent);

        $result = $this->helper->getExtensionLogFile(true);

        // Calculate expected URL-safe base64 deflate output
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
}
