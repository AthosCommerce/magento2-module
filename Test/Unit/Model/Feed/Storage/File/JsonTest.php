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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\Storage\File;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Math\Random;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\Storage\File\Json;

class JsonTest extends \PHPUnit\Framework\TestCase
{
    private $filesystemMock;

    private $randomMock;

    private $jsonSerializerMock;

    private $json;

    public function setUp(): void
    {
        $this->filesystemMock = $this->createMock(Filesystem::class);
        $this->randomMock = $this->createMock(Random::class);
        $this->jsonSerializerMock = $this->createMock(JsonSerializer::class);
        $this->json = new Json(
            $this->filesystemMock,
            $this->randomMock,
            $this->jsonSerializerMock
        );
    }

    public function testInitialize()
    {
        $testFile = 'athoscommerce/test.json';
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $writeFileMock = $this->createMock(Filesystem\File\WriteInterface::class);
        $writeDirectoryMock = $this->createMock(Filesystem\Directory\WriteInterface::class);
        $this->filesystemMock->expects($this->once())
            ->method('getDirectoryWrite')
            ->with(DirectoryList::VAR_DIR)
            ->willReturn($writeDirectoryMock);
        $writeDirectoryMock->expects($this->once())
            ->method('isExist')
            ->with($testFile)
            ->willReturn(false);
        $writeDirectoryMock->expects($this->once())
            ->method('openFile')
            ->with($testFile)
            ->willReturn($writeFileMock);

        $this->json->initialize('test', $feedSpecificationMock);
    }

    public function testAppendDataExceptionCase()
    {
        $this->expectException(\Exception::class);
        $this->json->appendData([]);
    }

    public function testAppendData()
    {
        $testFile = 'athoscommerce/test.json';
        $testData = [
            'test' => 'data'
        ];
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $writeFileMock = $this->createMock(Filesystem\File\WriteInterface::class);
        $writeDirectoryMock = $this->createMock(Filesystem\Directory\WriteInterface::class);
        $this->filesystemMock->expects($this->once())
            ->method('getDirectoryWrite')
            ->with(DirectoryList::VAR_DIR)
            ->willReturn($writeDirectoryMock);
        $writeDirectoryMock->expects($this->once())
            ->method('isExist')
            ->with($testFile)
            ->willReturn(false);
        $writeDirectoryMock->expects($this->once())
            ->method('openFile')
            ->with($testFile)
            ->willReturn($writeFileMock);
        $writeDirectoryMock->expects($this->once())
            ->method('isFile')
            ->with($testFile)
            ->willReturn(true);
        $this->jsonSerializerMock->expects($this->once())
            ->method('serialize')
            ->with($testData)
            ->willReturn(json_encode($testData));

        $this->json->initialize('test', $feedSpecificationMock);
        $this->json->appendData($testData);
    }
}
