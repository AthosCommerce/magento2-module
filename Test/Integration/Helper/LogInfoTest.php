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

namespace AthosCommerce\Feed\Test\Integration\Helper;

use AthosCommerce\Feed\Helper\LogInfo;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class LogInfoTest extends TestCase
{
    /**
     * @var LogInfo
     */
    private $helper;
    /**
     * @var File
     */
    private $fileDriver;
    /**
     * @var DirectoryList
     */
    private $directoryList;
    /**
     * @var string
     */
    private $testLogFilePath;

    /**
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->helper = $objectManager->get(LogInfo::class);
        $this->fileDriver = $objectManager->get(File::class);
        $this->directoryList = $objectManager->get(DirectoryList::class);

        // Set up actual file path for testing against real filesystem
        $logDir = $this->directoryList->getPath(DirectoryList::LOG);
        $this->testLogFilePath = $logDir . '/' . LogInfo::LOG['athoscommerce'];
    }

    protected function tearDown(): void
    {
        // Cleanup test artifact if still present
        if ($this->fileDriver->isExists($this->testLogFilePath)) {
            $this->fileDriver->deleteFile($this->testLogFilePath);
        }
    }

    public function testRealFileReadAndCompress(): void
    {
        $content = 'Random feed test log entry: ' . uniqid();
        $this->fileDriver->filePutContents($this->testLogFilePath, $content);


        $rawResult = $this->helper->getExtensionLogFile(false);
        $this->assertEquals($content, $rawResult);

        $compressedResult = $this->helper->getExtensionLogFile(true);
        $this->assertNotEmpty($compressedResult);
        $this->assertNotEquals($content, $compressedResult);
    }

    public function testRealFileDelete(): void
    {
        $this->fileDriver->filePutContents($this->testLogFilePath, 'Temporary file content');
        $this->assertTrue($this->fileDriver->isExists($this->testLogFilePath));

        $deleted = $this->helper->deleteExtensionLogFile();

        $this->assertTrue($deleted);
        $this->assertFalse($this->fileDriver->isExists($this->testLogFilePath));
    }
}
