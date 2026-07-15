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

class GenerateFeedTest extends \PHPUnit\Framework\TestCase
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

    public function setUp(): void
    {
        $this->collectionProcessorMock = $this->createMock(CollectionProcessor::class);
        $this->itemsGeneratorMock = $this->createMock(ItemsGenerator::class);
        $this->collectionConfigMock = $this->createMock(CollectionConfigInterface::class);
        $this->storageMock = $this->createMock(StorageInterface::class);
        $this->catalogStorageMock = $this->createMock(CatalogStorageInterface::class);
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
    }

    /** @test */
    public function generatesFeedAndStoresCatalogPayloadWhenCatalogUrlIsConfigured(): void
    {
        $feedSpecificationMock = $this->createFeedSpecificationMock(
            'json',
            'https://example.com/feed.json.gz',
            'https://example.com/catalog.json'
        );
        $collectionMock = $this->createCollectionMock();
        $taskMock = $this->createMock(TaskInterface::class);
        $productMock = $this->createMock(Product::class);
        $itemsData = [['sku' => 'sku-1'], ['sku' => 'sku-2']];

        $this->storageMock->method('getAdditionalData')->willReturn([]);
        $this->storageMock->expects($this->once())->method('isSupportedFormat')->with('json')->willReturn(true);
        $this->storageMock->expects($this->once())->method('initiate')->with($feedSpecificationMock);
        $this->storageMock->expects($this->once())->method('addData')->with($itemsData, 21);
        $this->storageMock->expects($this->once())->method('commit')->with(21);

        $this->catalogStorageMock->expects($this->once())->method('initiate')->with($feedSpecificationMock);
        $this->catalogStorageMock->expects($this->once())->method('addData')->with($itemsData, 21);

        $this->collectionConfigMock->expects($this->once())->method('getPageSize')->willReturn(2);
        $this->appConfigMock->expects($this->once())->method('isDebug')->willReturn(false);
        $this->appConfigMock->expects($this->once())
            ->method('getValue')
            ->with('product_metric_max_page')
            ->willReturn(5);

        $collectionMock->expects($this->once())->method('setPageSize')->with(2);
        $collectionMock->expects($this->once())->method('getLastPageNumber')->willReturn(1);
        $collectionMock->expects($this->once())->method('setCurPage')->with(1);
        $collectionMock->expects($this->once())->method('load')->willReturnSelf();
        $collectionMock->expects($this->once())->method('getItems')->willReturn([$productMock]);
        $collectionMock->expects($this->once())->method('clear');

        $this->collectionProcessorMock->expects($this->once())
            ->method('getCollection')
            ->with($feedSpecificationMock)
            ->willReturn($collectionMock);
        $this->collectionProcessorMock->expects($this->once())
            ->method('processAfterLoad')
            ->with($collectionMock, $feedSpecificationMock);
        $this->collectionProcessorMock->expects($this->once())
            ->method('processAfterFetchItems')
            ->with($collectionMock, $feedSpecificationMock);

        $this->itemsGeneratorMock->expects($this->exactly(2))
            ->method('resetDataProviders')
            ->with($feedSpecificationMock);
        $this->itemsGeneratorMock->expects($this->once())
            ->method('generate')
            ->with([$productMock], $feedSpecificationMock)
            ->willReturn($itemsData);
        $this->itemsGeneratorMock->expects($this->once())
            ->method('resetDataProvidersAfterFetchItems')
            ->with($feedSpecificationMock);

        $this->contextManagerMock->expects($this->once())
            ->method('setContextFromSpecification')
            ->with($feedSpecificationMock);
        $this->contextManagerMock->expects($this->once())->method('resetContext');

        $this->metricCollectorMock->method('collect');
        $this->metricCollectorMock->method('print');
        $this->metricCollectorMock->expects($this->once())
            ->method('reset')
            ->with(CollectorInterface::CODE_PRODUCT_FEED);

        $taskMock->expects($this->once())->method('setProductCount')->with(2)->willReturnSelf();
        $this->taskRepositoryMock->expects($this->once())->method('get')->with(21)->willReturn($taskMock);
        $this->taskRepositoryMock->expects($this->once())->method('save')->with($taskMock)->willReturn($taskMock);

        $this->loggerMock->method('info');
        $this->loggerMock->method('debug');

        $this->generateFeed->execute($feedSpecificationMock, 21);
    }

    /** @test */
    public function skipsCatalogStorageWhenCatalogUrlIsMissing(): void
    {
        $feedSpecificationMock = $this->createFeedSpecificationMock('json', 'https://example.com/feed.json', '');
        $collectionMock = $this->createCollectionMock();
        $taskMock = $this->createMock(TaskInterface::class);
        $itemsData = [['sku' => 'sku-1']];

        $this->storageMock->method('getAdditionalData')->willReturn([]);
        $this->storageMock->expects($this->once())->method('isSupportedFormat')->with('json')->willReturn(true);
        $this->storageMock->expects($this->once())->method('initiate');
        $this->storageMock->expects($this->once())->method('addData')->with($itemsData, 77);
        $this->storageMock->expects($this->once())->method('commit')->with(77);

        $this->catalogStorageMock->expects($this->once())->method('initiate');
        $this->catalogStorageMock->expects($this->never())->method('addData');

        $this->collectionConfigMock->expects($this->once())->method('getPageSize')->willReturn(1);
        $this->appConfigMock->expects($this->once())->method('isDebug')->willReturn(false);
        $this->appConfigMock->method('getValue')->willReturn(10);

        $collectionMock->expects($this->once())->method('setPageSize')->with(1);
        $collectionMock->expects($this->once())->method('getLastPageNumber')->willReturn(1);
        $collectionMock->expects($this->once())->method('setCurPage')->with(1);
        $collectionMock->expects($this->once())->method('load')->willReturnSelf();
        $collectionMock->expects($this->once())->method('getItems')->willReturn([]);
        $collectionMock->expects($this->once())->method('clear');

        $this->collectionProcessorMock->method('getCollection')->willReturn($collectionMock);
        $this->collectionProcessorMock->expects($this->once())->method('processAfterLoad');
        $this->collectionProcessorMock->expects($this->once())->method('processAfterFetchItems');

        $this->itemsGeneratorMock->expects($this->exactly(2))->method('resetDataProviders');
        $this->itemsGeneratorMock->expects($this->once())->method('generate')->willReturn($itemsData);
        $this->itemsGeneratorMock->expects($this->once())->method('resetDataProvidersAfterFetchItems');

        $this->contextManagerMock->method('setContextFromSpecification');
        $this->contextManagerMock->expects($this->once())->method('resetContext');

        $this->metricCollectorMock->method('collect');
        $this->metricCollectorMock->method('print');
        $this->metricCollectorMock->expects($this->once())->method('reset');

        $taskMock->expects($this->once())->method('setProductCount')->with(1)->willReturnSelf();
        $this->taskRepositoryMock->method('get')->willReturn($taskMock);
        $this->taskRepositoryMock->expects($this->once())->method('save')->with($taskMock)->willReturn($taskMock);

        $this->loggerMock->method('info');
        $this->loggerMock->method('debug');

        $this->generateFeed->execute($feedSpecificationMock, 77);
    }

    /** @test */
    public function throwsExceptionWhenPresignedUrlHasNoPath(): void
    {
        $feedSpecificationMock = $this->createFeedSpecificationMock('json', 'https://example.com', '');

        $this->storageMock->expects($this->never())->method('isSupportedFormat');
        $this->loggerMock->method('info');
        $this->loggerMock->expects($this->once())->method('error');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Not able to set the format(json) from PreSignedUrl');

        $this->generateFeed->execute($feedSpecificationMock, 3);
    }

    /** @test */
    public function throwsExceptionWhenFormatIsNotSupported(): void
    {
        $feedSpecificationMock = $this->createFeedSpecificationMock('xml', 'https://example.com/feed.xml', '');

        $this->storageMock->expects($this->once())->method('isSupportedFormat')->with('xml')->willReturn(false);
        $this->loggerMock->method('info');
        $this->loggerMock->expects($this->once())->method('error');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('xml is not supported format');

        $this->generateFeed->execute($feedSpecificationMock, 4);
    }

    /** @test */
    public function rollsBackStorageWhenPageProcessingFails(): void
    {
        $feedSpecificationMock = $this->createFeedSpecificationMock('json', 'https://example.com/feed.json', '');
        $collectionMock = $this->createCollectionMock();
        $productMock = $this->createMock(Product::class);

        $this->storageMock->method('getAdditionalData')->willReturn([]);
        $this->storageMock->expects($this->once())->method('isSupportedFormat')->willReturn(true);
        $this->storageMock->expects($this->once())->method('initiate');
        $this->storageMock->expects($this->once())->method('rollback');
        $this->storageMock->expects($this->never())->method('commit');

        $this->catalogStorageMock->expects($this->once())->method('initiate');

        $this->collectionConfigMock->expects($this->once())->method('getPageSize')->willReturn(1);
        $this->appConfigMock->expects($this->once())->method('isDebug')->willReturn(false);
        $this->appConfigMock->method('getValue')->willReturn(10);

        $collectionMock->expects($this->once())->method('setPageSize')->with(1);
        $collectionMock->expects($this->once())->method('getLastPageNumber')->willReturn(1);
        $collectionMock->expects($this->once())->method('setCurPage')->with(1);
        $collectionMock->expects($this->once())->method('load')->willReturnSelf();
        $collectionMock->expects($this->once())->method('getItems')->willReturn([$productMock]);
        $collectionMock->expects($this->never())->method('clear');

        $this->collectionProcessorMock->method('getCollection')->willReturn($collectionMock);
        $this->collectionProcessorMock->expects($this->once())->method('processAfterLoad');
        $this->collectionProcessorMock->expects($this->never())->method('processAfterFetchItems');

        $this->itemsGeneratorMock->expects($this->once())->method('resetDataProviders');
        $this->itemsGeneratorMock->expects($this->once())
            ->method('generate')
            ->with([$productMock], $feedSpecificationMock)
            ->willThrowException(new Exception('generation failed'));
        $this->itemsGeneratorMock->expects($this->never())->method('resetDataProvidersAfterFetchItems');

        $this->contextManagerMock->expects($this->once())->method('setContextFromSpecification');
        $this->contextManagerMock->expects($this->never())->method('resetContext');

        $this->metricCollectorMock->method('collect');
        $this->metricCollectorMock->method('print');
        $this->metricCollectorMock->expects($this->never())->method('reset');

        $this->taskRepositoryMock->expects($this->never())->method('get');
        $this->taskRepositoryMock->expects($this->never())->method('save');

        $this->loggerMock->method('info');
        $this->loggerMock->expects($this->once())->method('error');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('generation failed');

        $this->generateFeed->execute($feedSpecificationMock, 8);
    }

    private function createFeedSpecificationMock(
        string $format,
        string $preSignedUrl,
        string $catalogPreSignedUrl
    ): FeedSpecificationInterface {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getFormat')->willReturn($format);
        $feedSpecificationMock->method('getPreSignedUrl')->willReturn($preSignedUrl);
        $feedSpecificationMock->method('setFormat')->willReturnSelf();
        $feedSpecificationMock->method('getCatalogPreSignedUrl')->willReturn($catalogPreSignedUrl);

        return $feedSpecificationMock;
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
