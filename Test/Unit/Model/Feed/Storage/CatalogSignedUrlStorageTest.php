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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\Storage;

use AthosCommerce\Feed\Api\AppConfigInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Api\TaskRepositoryInterface;
use AthosCommerce\Feed\Model\Aws\PreSignedUrl;
use AthosCommerce\Feed\Model\Feed\Storage\CatalogSignedUrlStorage;
use AthosCommerce\Feed\Model\Feed\Storage\File\FileFactory;
use AthosCommerce\Feed\Model\Feed\Storage\File\NameGenerator;
use AthosCommerce\Feed\Model\Feed\Storage\FileInterface;
use AthosCommerce\Feed\Model\Feed\Storage\FormatterInterface;
use AthosCommerce\Feed\Model\Feed\Storage\FormatterPool;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

class CatalogSignedUrlStorageTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormatterPool|MockObject */
    private $formatterPoolMock;

    /** @var PreSignedUrl|MockObject */
    private $preSignedUrlMock;

    /** @var NameGenerator|MockObject */
    private $nameGeneratorMock;

    /** @var FileFactory|MockObject */
    private $fileFactoryMock;

    /** @var AppConfigInterface|MockObject */
    private $appConfigMock;

    /** @var TaskInterface|MockObject */
    private $taskInterfaceMock;

    /** @var TaskRepositoryInterface|MockObject */
    private $taskRepositoryMock;

    /** @var CatalogSignedUrlStorage */
    private $preSignedUrlStorage;

    /** @var string[] */
    private $temporaryFiles = [];

    public function setUp(): void
    {
        $this->formatterPoolMock = $this->createMock(FormatterPool::class);
        $this->preSignedUrlMock = $this->createMock(PreSignedUrl::class);
        $this->nameGeneratorMock = $this->createMock(NameGenerator::class);
        $this->fileFactoryMock = $this->createMock(FileFactory::class);
        $this->appConfigMock = $this->createMock(AppConfigInterface::class);
        $this->taskInterfaceMock = $this->createMock(TaskInterface::class);
        $this->taskRepositoryMock = $this->createMock(TaskRepositoryInterface::class);
        $this->preSignedUrlStorage = new CatalogSignedUrlStorage(
            $this->formatterPoolMock,
            $this->preSignedUrlMock,
            $this->nameGeneratorMock,
            $this->fileFactoryMock,
            $this->appConfigMock,
            $this->taskInterfaceMock,
            $this->taskRepositoryMock
        );
    }

    public function tearDown(): void
    {
        foreach ($this->temporaryFiles as $temporaryFile) {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }

    /** @test */
    public function isSupportedFormatReturnsTrueWhenFormatterAndFileFactorySupportFormat(): void
    {
        $testFormat = 'csv';
        $formatterInterfaceMock = $this->createMock(FormatterInterface::class);
        $this->formatterPoolMock->expects($this->once())
            ->method('get')
            ->with($testFormat)
            ->willReturn($formatterInterfaceMock);
        $this->fileFactoryMock->expects($this->once())
            ->method('isSupportedFormat')
            ->with($testFormat)
            ->willReturn(true);

        $this->assertTrue($this->preSignedUrlStorage->isSupportedFormat($testFormat));
    }

    /** @test */
    public function isSupportedFormatReturnsFalseWhenFileFactoryDoesNotSupportFormat(): void
    {
        $testFormat = 'csv';
        $formatterInterfaceMock = $this->createMock(FormatterInterface::class);
        $this->formatterPoolMock->expects($this->once())
            ->method('get')
            ->with($testFormat)
            ->willReturn($formatterInterfaceMock);
        $this->fileFactoryMock->expects($this->once())
            ->method('isSupportedFormat')
            ->with($testFormat)
            ->willReturn(false);

        $this->assertFalse($this->preSignedUrlStorage->isSupportedFormat($testFormat));
    }

    /** @test */
    public function initiateCreatesCsvFileAndInitializesItWithGeneratedName(): void
    {
        $fileMock = $this->createMock(FileInterface::class);
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $formatterInterfaceMock = $this->createMock(FormatterInterface::class);
        $this->formatterPoolMock->expects($this->once())
            ->method('get')
            ->with('csv')
            ->willReturn($formatterInterfaceMock);
        $this->fileFactoryMock->expects($this->once())
            ->method('isSupportedFormat')
            ->with('csv')
            ->willReturn(true);
        $this->fileFactoryMock->expects($this->once())
            ->method('create')
            ->with('csv')
            ->willReturn($fileMock);
        $this->nameGeneratorMock->expects($this->once())
            ->method('generate')
            ->with(['catalog', 'aws_presigned'])
            ->willReturn('generated_name');
        $fileMock->expects($this->once())
            ->method('initialize')
            ->with('generated_name', $feedSpecificationMock);

        $this->preSignedUrlStorage->initiate($feedSpecificationMock);
    }

    /** @test */
    public function initiateThrowsWhenFormatIsNotSupported(): void
    {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $formatterInterfaceMock = $this->createMock(FormatterInterface::class);
        $this->formatterPoolMock->expects($this->once())
            ->method('get')
            ->with('csv')
            ->willReturn($formatterInterfaceMock);
        $this->fileFactoryMock->expects($this->once())
            ->method('isSupportedFormat')
            ->with('csv')
            ->willReturn(false);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('csv is not supported format');

        $this->preSignedUrlStorage->initiate($feedSpecificationMock);
    }

    /** @test */
    public function addDataThrowsWhenFileIsNotInitialized(): void
    {
        $this->expectExceptionMessage('file is not initialized yet');
        $this->expectException(Exception::class);

        $this->preSignedUrlStorage->addData([], 1);
    }

    /** @test */
    public function addDataThrowsWhenSpecificationIsNotInitialized(): void
    {
        $fileMock = $this->createMock(FileInterface::class);
        $this->setPrivateProperty('file', $fileMock);

        $this->expectExceptionMessage('specification is not initialized yet');
        $this->expectException(Exception::class);

        $this->preSignedUrlStorage->addData([], 1);
    }

    /** @test */
    public function addDataFormatsAndAppendsDataWhenStorageIsReady(): void
    {
        $testData = ['test' => 'data'];
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $fileMock = $this->createMock(FileInterface::class);
        $formatterInterfaceMock = $this->createMock(FormatterInterface::class);

        $this->fileFactoryMock->expects($this->once())
            ->method('isSupportedFormat')
            ->with('csv')
            ->willReturn(true);
        $this->formatterPoolMock->expects($this->exactly(2))
            ->method('get')
            ->with('csv')
            ->willReturn($formatterInterfaceMock);
        $formatterInterfaceMock->expects($this->once())
            ->method('format')
            ->with($testData, $feedSpecificationMock)
            ->willReturn(array_merge($testData, ['formatted' => true]));
        $fileMock->expects($this->once())
            ->method('appendData')
            ->with([
                'test' => 'data',
                'formatted' => true
            ]);

        $this->setPrivateProperty('file', $fileMock);
        $this->setPrivateProperty('specification', $feedSpecificationMock);

        $this->preSignedUrlStorage->addData($testData, 1);
    }

    /** @test */
    public function addDataThrowsWhenFormatIsNotSupported(): void
    {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $fileMock = $this->createMock(FileInterface::class);
        $formatterInterfaceMock = $this->createMock(FormatterInterface::class);

        $this->fileFactoryMock->expects($this->once())
            ->method('isSupportedFormat')
            ->with('csv')
            ->willReturn(false);
        $this->formatterPoolMock->expects($this->once())
            ->method('get')
            ->with('csv')
            ->willReturn($formatterInterfaceMock);

        $this->setPrivateProperty('file', $fileMock);
        $this->setPrivateProperty('specification', $feedSpecificationMock);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('csv is not supported format');

        $this->preSignedUrlStorage->addData([], 1);
    }

    /** @test */
    public function commitThrowsWhenFileIsNotInitialized(): void
    {
        $this->expectExceptionMessage('file is not initialized yet');
        $this->expectException(Exception::class);

        $this->preSignedUrlStorage->commit(1);
    }

    /** @test */
    public function commitSavesCatalogPayloadAndDeletesTheFileWhenDebugIsDisabled(): void
    {
        $filePath = $this->createTemporaryFile('catalog feed content');
        $fileMock = $this->createMock(FileInterface::class);
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getCatalogPreSignedUrl')
            ->willReturn('https://example.com/catalog.json');

        $this->setPrivateProperty('file', $fileMock);
        $this->setPrivateProperty('specification', $feedSpecificationMock);

        $fileMock->expects($this->once())
            ->method('commit');
        $fileMock->expects($this->once())
            ->method('getAbsolutePath')
            ->willReturn($filePath);
        $this->preSignedUrlMock->expects($this->once())
            ->method('save')
            ->with($feedSpecificationMock, ['type' => 'stream', 'file' => $filePath]);
        $this->appConfigMock->expects($this->once())
            ->method('isDebug')
            ->willReturn(false);
        $this->appConfigMock->expects($this->never())
            ->method('getValue');
        $fileMock->expects($this->once())
            ->method('delete');

        $taskMock = $this->createMock(TaskInterface::class);
        $taskMock->expects($this->once())
            ->method('setFileSize')
            ->with(filesize($filePath))
            ->willReturnSelf();
        $this->taskRepositoryMock->expects($this->once())
            ->method('get')
            ->with(1)
            ->willReturn($taskMock);
        $this->taskRepositoryMock->expects($this->once())
            ->method('save')
            ->with($taskMock)
            ->willReturn($taskMock);

        $this->preSignedUrlStorage->commit(1);
    }

    /** @test */
    public function commitSkipsDeletingTheFileWhenDeleteFileIsFalse(): void
    {
        $filePath = $this->createTemporaryFile('catalog feed content');
        $fileMock = $this->createMock(FileInterface::class);
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getPreSignedUrl')->willReturn('https://example.com/catalog.json');
        $feedSpecificationMock->method('getCatalogPreSignedUrl')->willReturn('https://example.com/catalog.json');

        $this->setPrivateProperty('file', $fileMock);
        $this->setPrivateProperty('specification', $feedSpecificationMock);

        $fileMock->expects($this->once())
            ->method('commit');
        $fileMock->expects($this->once())
            ->method('getAbsolutePath')
            ->willReturn($filePath);
        $fileMock->expects($this->never())->method('delete');

        $taskMock = $this->createMock(TaskInterface::class);
        $taskMock->method('setFileSize')->willReturnSelf();
        $this->taskRepositoryMock->method('get')->willReturn($taskMock);
        $this->taskRepositoryMock->method('save')->willReturn($taskMock);
        $this->preSignedUrlMock->expects($this->once())->method('save');
        $this->appConfigMock->method('isDebug')->willReturn(false);

        $this->preSignedUrlStorage->commit(1, false);
    }

    /** @test */
    public function rollbackDelegatesToFileRollback(): void
    {
        $fileMock = $this->createMock(FileInterface::class);
        $this->setPrivateProperty('file', $fileMock);

        $fileMock->expects($this->once())->method('rollback');

        $this->preSignedUrlStorage->rollback();
    }

    /** @test */
    public function getAdditionalDataReturnsFileInfoWithTheFileName(): void
    {
        $fileMock = $this->createMock(FileInterface::class);
        $this->setPrivateProperty('file', $fileMock);

        $fileMock->expects($this->once())
            ->method('getFileInfo')
            ->willReturn([
                'size' => 333,
                'blocks' => 3333,
            ]);
        $fileMock->expects($this->once())
            ->method('getName')
            ->willReturn('test_name');

        $this->assertSame(
            [
                'size' => 333,
                'blocks' => 3333,
                'name' => 'test_name'
            ],
            $this->preSignedUrlStorage->getAdditionalData()
        );
    }

    private function setPrivateProperty(string $property, $value): void
    {
        $reflectionClass = new \ReflectionClass(CatalogSignedUrlStorage::class);
        $reflectionProperty = $reflectionClass->getProperty($property);
        $reflectionProperty->setValue($this->preSignedUrlStorage, $value);
    }

    private function createTemporaryFile(string $content): string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'catalog_signed_url_storage_');
        if ($filePath === false) {
            throw new Exception('Unable to create temporary file');
        }

        file_put_contents($filePath, $content);
        $this->temporaryFiles[] = $filePath;

        return $filePath;
    }
}
