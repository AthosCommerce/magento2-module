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

namespace AthosCommerce\Feed\Test\Unit\Model;

use AthosCommerce\Feed\Api\AppConfigInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Api\TaskRepositoryInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\CollectionProcessor;
use AthosCommerce\Feed\Model\Feed\CatalogStorageInterface;
use AthosCommerce\Feed\Model\Feed\CollectionConfigInterface;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\StorageInterface;
use AthosCommerce\Feed\Model\GenerateFeed;
use AthosCommerce\Feed\Model\ItemsGenerator;
use AthosCommerce\Feed\Model\Metric\CollectorInterface;
use Exception;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CatalogStorageWithCountDouble implements CatalogStorageInterface
{
    public function initiate(FeedSpecificationInterface $feedSpecification): void
    {
    }

    public function addData(array $data, int $id): void
    {
    }

    public function commit(int $id, bool $deleteFile = true): void
    {
    }

    public function rollback(): void
    {
    }

    public function getAdditionalData(): array
    {
        return [];
    }

    public function isSupportedFormat(string $format): bool
    {
        return true;
    }

    public function getCatalogRowCount(): int
    {
        return 0;
    }
}

class GenerateFeedTest extends TestCase
{
    /** @var CollectionProcessor|MockObject */
    private $collectionProcessorMock;

    /** @var ItemsGenerator|MockObject */
    private $itemsGeneratorMock;

    /** @var CollectionConfigInterface|MockObject */
    private $collectionConfigMock;

    /** @var StorageInterface|MockObject */
    private $storageMock;

    /** @var CatalogStorageInterface|MockObject */
    private $catalogStorageMock;

    /** @var ContextManagerInterface|MockObject */
    private $contextManagerMock;

    /** @var CollectorInterface|MockObject */
    private $metricCollectorMock;

    /** @var AppConfigInterface|MockObject */
    private $appConfigMock;

    /** @var TaskRepositoryInterface|MockObject */
    private $taskRepositoryMock;

    /** @var AthosCommerceLogger|MockObject */
    private $loggerMock;

    /** @var GenerateFeed */
    private $generateFeed;

    protected function setUp(): void
    {
        $this->collectionProcessorMock = $this->createMock(CollectionProcessor::class);
        $this->itemsGeneratorMock = $this->createMock(ItemsGenerator::class);
        $this->collectionConfigMock = $this->createMock(CollectionConfigInterface::class);
        $this->storageMock = $this->createMock(StorageInterface::class);
        $this->catalogStorageMock = $this->getMockBuilder(CatalogStorageWithCountDouble::class)
            ->getMock();
        $this->contextManagerMock = $this->createMock(ContextManagerInterface::class);
        $this->metricCollectorMock = $this->createMock(CollectorInterface::class);
        $this->appConfigMock = $this->createMock(AppConfigInterface::class);
        $this->taskRepositoryMock = $this->createMock(TaskRepositoryInterface::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->generateFeed = new GenerateFeed(
            $this->collectionProcessorMock,
            $this->itemsGeneratorMock,
            $this->collectionConfigMock,
            $this->storageMock,
            $this->catalogStorageMock,
            $this->contextManagerMock,
            $this->metricCollectorMock,
            $this->appConfigMock,
            $this->taskRepositoryMock,
            $this->loggerMock
        );

        $this->storageMock->method('getAdditionalData')->willReturn([]);
        $this->metricCollectorMock->method('collect');
        $this->metricCollectorMock->method('print');
        $this->loggerMock->method('info');
        $this->loggerMock->method('debug');
        $this->loggerMock->method('error');
    }

    public function testExecuteStoresProductAndCatalogCountsAndCommitsBothStoragesWhenCatalogUrlExists(): void
    {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $collectionMock = $this->createCollectionMock();
        $productMock = $this->createMock(Product::class);
        $taskMock = $this->createMock(TaskInterface::class);
        $itemsData = [['entity_id' => 1], ['entity_id' => 2]];

        $feedSpecificationMock->method('getFormat')->willReturn('json');
        $feedSpecificationMock->expects($this->once())
            ->method('getPreSignedUrl')
            ->willReturn('https://example.com/feed.json.gz');
        $feedSpecificationMock->expects($this->once())
            ->method('setFormat')
            ->with('json')
            ->willReturnSelf();
        $feedSpecificationMock->method('getCatalogPreSignedUrl')
            ->willReturn('https://example.com/catalog.csv');

        $this->storageMock->expects($this->once())->method('isSupportedFormat')->with('json')->willReturn(true);
        $this->storageMock->expects($this->once())->method('initiate')->with($feedSpecificationMock);
        $this->catalogStorageMock->expects($this->once())->method('initiate')->with($feedSpecificationMock);

        $this->collectionProcessorMock->expects($this->once())->method('getCollection')->with($feedSpecificationMock)->willReturn($collectionMock);
        $this->collectionConfigMock->expects($this->once())->method('getPageSize')->willReturn(10);
        $this->appConfigMock->expects($this->once())->method('isDebug')->willReturn(false);
        $this->appConfigMock->expects($this->once())->method('getValue')->with('product_metric_max_page')->willReturn(10);

        $collectionMock->expects($this->once())->method('setPageSize')->with(10)->willReturnSelf();
        $collectionMock->expects($this->once())->method('getLastPageNumber')->willReturn(1);
        $collectionMock->expects($this->once())->method('setCurPage')->with(1)->willReturnSelf();
        $collectionMock->expects($this->once())->method('load')->willReturnSelf();
        $collectionMock->expects($this->once())->method('getItems')->willReturn([$productMock]);
        $collectionMock->expects($this->once())->method('clear')->willReturnSelf();

        $this->collectionProcessorMock->expects($this->once())->method('processAfterLoad')->with($collectionMock, $feedSpecificationMock);
        $this->collectionProcessorMock->expects($this->once())->method('processAfterFetchItems')->with($collectionMock, $feedSpecificationMock);

        $this->itemsGeneratorMock->expects($this->once())->method('generate')->with([$productMock], $feedSpecificationMock)->willReturn($itemsData);
        $this->itemsGeneratorMock->expects($this->once())->method('resetDataProvidersAfterFetchItems')->with($feedSpecificationMock);
        $this->itemsGeneratorMock->expects($this->exactly(2))->method('resetDataProviders')->with($feedSpecificationMock);

        $this->storageMock->expects($this->once())->method('addData')->with($itemsData, 11);
        $this->catalogStorageMock->expects($this->once())->method('addData')->with($itemsData, 11);
        $this->catalogStorageMock->expects($this->once())->method('getCatalogRowCount')->willReturn(7);

        $taskMock->expects($this->once())->method('setProductCount')->with(2)->willReturnSelf();
        $taskMock->expects($this->once())->method('setCatalogCount')->with(7)->willReturnSelf();
        $this->taskRepositoryMock->expects($this->once())->method('get')->with(11)->willReturn($taskMock);
        $this->taskRepositoryMock->expects($this->once())->method('save')->with($taskMock)->willReturn($taskMock);

        $this->catalogStorageMock->expects($this->once())->method('commit')->with(11);
        $this->storageMock->expects($this->once())->method('commit')->with(11);

        $this->contextManagerMock->expects($this->once())->method('setContextFromSpecification')->with($feedSpecificationMock);
        $this->contextManagerMock->expects($this->once())->method('resetContext');
        $this->metricCollectorMock->expects($this->once())->method('reset')->with(CollectorInterface::CODE_PRODUCT_FEED);

        $this->generateFeed->execute($feedSpecificationMock, 11);
    }

    public function testExecuteSkipsCatalogAddDataAndCommitWhenCatalogUrlIsMissing(): void
    {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $collectionMock = $this->createCollectionMock();
        $taskMock = $this->createMock(TaskInterface::class);

        $feedSpecificationMock->method('getFormat')->willReturn('json');
        $feedSpecificationMock->expects($this->once())->method('getPreSignedUrl')->willReturn('https://example.com/feed.json');
        $feedSpecificationMock->expects($this->once())->method('setFormat')->with('json')->willReturnSelf();
        $feedSpecificationMock->method('getCatalogPreSignedUrl')->willReturn('');

        $this->storageMock->expects($this->once())->method('isSupportedFormat')->with('json')->willReturn(true);
        $this->storageMock->expects($this->once())->method('initiate')->with($feedSpecificationMock);
        $this->catalogStorageMock->expects($this->once())->method('initiate')->with($feedSpecificationMock);

        $this->collectionProcessorMock->expects($this->once())->method('getCollection')->willReturn($collectionMock);
        $this->collectionConfigMock->expects($this->once())->method('getPageSize')->willReturn(10);
        $this->appConfigMock->expects($this->once())->method('isDebug')->willReturn(false);
        $this->appConfigMock->expects($this->once())->method('getValue')->with('product_metric_max_page')->willReturn(10);

        $collectionMock->method('setPageSize')->willReturnSelf();
        $collectionMock->expects($this->once())->method('getLastPageNumber')->willReturn(1);
        $collectionMock->method('setCurPage')->willReturnSelf();
        $collectionMock->method('load')->willReturnSelf();
        $collectionMock->expects($this->once())->method('getItems')->willReturn([]);
        $collectionMock->method('clear')->willReturnSelf();

        $this->collectionProcessorMock->expects($this->once())->method('processAfterLoad');
        $this->collectionProcessorMock->expects($this->once())->method('processAfterFetchItems');

        $this->itemsGeneratorMock->expects($this->once())->method('generate')->willReturn([]);
        $this->itemsGeneratorMock->expects($this->once())->method('resetDataProvidersAfterFetchItems')->with($feedSpecificationMock);
        $this->itemsGeneratorMock->expects($this->exactly(2))->method('resetDataProviders')->with($feedSpecificationMock);

        $this->storageMock->expects($this->once())->method('addData')->with([], 12);
        $this->catalogStorageMock->expects($this->never())->method('addData');
        $this->catalogStorageMock->expects($this->never())->method('getCatalogRowCount');
        $this->catalogStorageMock->expects($this->never())->method('commit');

        $taskMock->expects($this->once())->method('setProductCount')->with(0)->willReturnSelf();
        $taskMock->expects($this->once())->method('setCatalogCount')->with(0)->willReturnSelf();
        $this->taskRepositoryMock->expects($this->once())->method('get')->with(12)->willReturn($taskMock);
        $this->taskRepositoryMock->expects($this->once())->method('save')->with($taskMock);

        $this->storageMock->expects($this->once())->method('commit')->with(12);

        $this->contextManagerMock->expects($this->once())->method('setContextFromSpecification')->with($feedSpecificationMock);
        $this->contextManagerMock->expects($this->once())->method('resetContext');
        $this->metricCollectorMock->expects($this->once())->method('reset')->with(CollectorInterface::CODE_PRODUCT_FEED);

        $this->generateFeed->execute($feedSpecificationMock, 12);
    }

    public function testExecuteRollsBackAndRethrowsWhenGenerationFails(): void
    {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $collectionMock = $this->createCollectionMock();
        $productMock = $this->createMock(Product::class);

        $feedSpecificationMock->method('getFormat')->willReturn('json');
        $feedSpecificationMock->expects($this->once())->method('getPreSignedUrl')->willReturn('https://example.com/feed.json');
        $feedSpecificationMock->expects($this->once())->method('setFormat')->with('json')->willReturnSelf();

        $this->storageMock->expects($this->once())->method('isSupportedFormat')->willReturn(true);
        $this->storageMock->expects($this->once())->method('initiate');
        $this->catalogStorageMock->expects($this->once())->method('initiate');

        $this->collectionProcessorMock->expects($this->once())->method('getCollection')->willReturn($collectionMock);
        $this->collectionConfigMock->expects($this->once())->method('getPageSize')->willReturn(10);
        $this->appConfigMock->expects($this->once())->method('isDebug')->willReturn(false);
        $this->appConfigMock->expects($this->once())->method('getValue')->with('product_metric_max_page')->willReturn(10);

        $collectionMock->method('setPageSize')->willReturnSelf();
        $collectionMock->expects($this->once())->method('getLastPageNumber')->willReturn(1);
        $collectionMock->method('setCurPage')->willReturnSelf();
        $collectionMock->method('load')->willReturnSelf();
        $collectionMock->expects($this->once())->method('getItems')->willReturn([$productMock]);

        $this->collectionProcessorMock->expects($this->once())->method('processAfterLoad');
        $this->itemsGeneratorMock->expects($this->once())
            ->method('generate')
            ->willThrowException(new Exception('generation failed'));

        $this->storageMock->expects($this->once())->method('rollback');
        $this->storageMock->expects($this->never())->method('commit');
        $this->catalogStorageMock->expects($this->never())->method('commit');
        $this->taskRepositoryMock->expects($this->never())->method('get');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('generation failed');

        $this->generateFeed->execute($feedSpecificationMock, 13);
    }

    public function testExecuteThrowsExceptionWhenPreSignedUrlHasNoPath(): void
    {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);

        $feedSpecificationMock->method('getFormat')->willReturn('json');
        $feedSpecificationMock->expects($this->once())->method('getPreSignedUrl')->willReturn('https://example.com');
        $feedSpecificationMock->expects($this->never())->method('setFormat');

        $this->storageMock->expects($this->never())->method('isSupportedFormat');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Not able to set the format');

        $this->generateFeed->execute($feedSpecificationMock, 14);
    }

    public function testExecuteThrowsExceptionWhenFormatIsUnsupported(): void
    {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);

        $feedSpecificationMock->method('getFormat')->willReturn('xml');
        $feedSpecificationMock->expects($this->once())->method('getPreSignedUrl')->willReturn('https://example.com/feed.xml');
        $feedSpecificationMock->expects($this->once())->method('setFormat')->with('xml')->willReturnSelf();

        $this->storageMock->expects($this->once())->method('isSupportedFormat')->with('xml')->willReturn(false);
        $this->storageMock->expects($this->never())->method('initiate');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('xml is not supported format');

        $this->generateFeed->execute($feedSpecificationMock, 15);
    }

    private function createCollectionMock(): Collection&MockObject
    {
        $collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'setPageSize',
                'getLastPageNumber',
                'setCurPage',
                'load',
                'getSelect',
                'getItems',
                'clear',
            ])
            ->getMock();

        $collectionMock->method('getSelect')->willReturn(
            new class {
                public function __toString(): string
                {
                    return 'SELECT 1';
                }
            }
        );

        return $collectionMock;
    }
}
