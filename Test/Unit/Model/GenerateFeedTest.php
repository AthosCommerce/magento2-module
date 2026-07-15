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

namespace AthosCommerce\Feed\Test\Unit\Model;

use AthosCommerce\Feed\Api\AppConfigInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Api\TaskRepositoryInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\CollectionProcessor;
use AthosCommerce\Feed\Model\Feed\CollectionConfigInterface;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\StorageInterface;
use AthosCommerce\Feed\Model\GenerateFeed;
use AthosCommerce\Feed\Model\ItemsGenerator;
use AthosCommerce\Feed\Model\Metric\CollectorInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use PHPUnit\Framework\TestCase;

class GenerateFeedTest extends TestCase
{
    /**
     * @var CollectionProcessor|\PHPUnit\Framework\MockObject\MockObject
     */
    private $collectionProcessorMock;

    /**
     * @var ItemsGenerator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $itemsGeneratorMock;

    /**
     * @var CollectionConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $collectionConfigMock;

    /**
     * @var StorageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $storageMock;

    /**
     * @var ContextManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contextManagerMock;

    /**
     * @var CollectorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $metricCollectorMock;

    /**
     * @var AppConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $appConfigMock;

    /**
     * @var TaskRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $taskRepositoryMock;

    /**
     * @var AthosCommerceLogger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var GenerateFeed
     */
    private $generateFeed;

    public function setUp(): void
    {
        $this->collectionProcessorMock = $this->createMock(CollectionProcessor::class);
        $this->itemsGeneratorMock = $this->createMock(ItemsGenerator::class);
        $this->collectionConfigMock = $this->createMock(CollectionConfigInterface::class);
        $this->storageMock = $this->createMock(StorageInterface::class);
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
            $this->contextManagerMock,
            $this->metricCollectorMock,
            $this->appConfigMock,
            $this->taskRepositoryMock,
            $this->loggerMock
        );

        $this->storageMock->method('getAdditionalData')
            ->willReturn([]);

        $this->metricCollectorMock->method('collect');
        $this->metricCollectorMock->method('print');
        $this->loggerMock->method('info');
        $this->loggerMock->method('debug');
        $this->loggerMock->method('error');
    }

    public function testExecuteGeneratesFeedAndSavesProductCount(): void
    {
        $taskId = 1;
        $pageSize = 10;
        $format = 'json';
        $curPageCalls = [];

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $taskMock = $this->createMock(TaskInterface::class);
        $productMock = $this->createMock(Product::class);

        $generatedItems = [
            [
                'entity_id' => 1,
                'sku' => 'product-1',
            ],
            [
                'entity_id' => 2,
                'sku' => 'product-2',
            ],
        ];

        $feedSpecificationMock->expects($this->any())
            ->method('getFormat')
            ->willReturn($format);

        $feedSpecificationMock->expects($this->once())
            ->method('getPreSignedUrl')
            ->willReturn('https://example.com/path/to/feed.json');

        $feedSpecificationMock->expects($this->once())
            ->method('setFormat')
            ->with('json')
            ->willReturnSelf();

        $this->storageMock->expects($this->once())
            ->method('isSupportedFormat')
            ->with($format)
            ->willReturn(true);

        $this->itemsGeneratorMock->expects($this->exactly(2))
            ->method('resetDataProviders')
            ->with($feedSpecificationMock);

        $this->contextManagerMock->expects($this->once())
            ->method('setContextFromSpecification')
            ->with($feedSpecificationMock);

        $this->storageMock->expects($this->once())
            ->method('initiate')
            ->with($feedSpecificationMock);

        $this->collectionProcessorMock->expects($this->once())
            ->method('getCollection')
            ->with($feedSpecificationMock)
            ->willReturn($collectionMock);

        $this->collectionConfigMock->expects($this->once())
            ->method('getPageSize')
            ->willReturn($pageSize);

        $collectionMock->expects($this->once())
            ->method('setPageSize')
            ->with($pageSize)
            ->willReturnSelf();

        $this->appConfigMock->expects($this->once())
            ->method('isDebug')
            ->willReturn(false);

        $collectionMock->expects($this->once())
            ->method('getLastPageNumber')
            ->willReturn(1);

        $this->appConfigMock->expects($this->once())
            ->method('getValue')
            ->with('product_metric_max_page')
            ->willReturn(10);

        $collectionMock->expects($this->once())
            ->method('setCurPage')
            ->willReturnCallback(function ($page) use (&$curPageCalls, $collectionMock) {
                $curPageCalls[] = $page;
                return $collectionMock;
            });

        $collectionMock->expects($this->once())
            ->method('load')
            ->willReturnSelf();

        $collectionMock->expects($this->any())
            ->method('getSelect')
            ->willReturn($selectMock);

        $selectMock->expects($this->any())
            ->method('__toString')
            ->willReturn('SELECT * FROM catalog_product_entity');

        $this->collectionProcessorMock->expects($this->once())
            ->method('processAfterLoad')
            ->with($collectionMock, $feedSpecificationMock);

        $collectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$productMock]);

        $this->itemsGeneratorMock->expects($this->once())
            ->method('generate')
            ->with([$productMock], $feedSpecificationMock)
            ->willReturn($generatedItems);

        $this->storageMock->expects($this->once())
            ->method('addData')
            ->with($generatedItems, $taskId);

        $this->itemsGeneratorMock->expects($this->once())
            ->method('resetDataProvidersAfterFetchItems')
            ->with($feedSpecificationMock);

        $collectionMock->expects($this->once())
            ->method('clear')
            ->willReturnSelf();

        $this->collectionProcessorMock->expects($this->once())
            ->method('processAfterFetchItems')
            ->with($collectionMock, $feedSpecificationMock);

        $this->taskRepositoryMock->expects($this->once())
            ->method('get')
            ->with($taskId)
            ->willReturn($taskMock);

        $taskMock->expects($this->once())
            ->method('setProductCount')
            ->with(2)
            ->willReturnSelf();

        $this->taskRepositoryMock->expects($this->once())
            ->method('save')
            ->with($taskMock)
            ->willReturn($taskMock);

        $this->storageMock->expects($this->once())
            ->method('commit')
            ->with($taskId);

        $this->metricCollectorMock->expects($this->once())
            ->method('reset')
            ->with(CollectorInterface::CODE_PRODUCT_FEED);

        $this->contextManagerMock->expects($this->once())
            ->method('resetContext');

        $this->generateFeed->execute($feedSpecificationMock, $taskId);

        $this->assertSame([1], $curPageCalls);
    }

    public function testExecuteProcessesMultiplePagesWithoutUsingAtMatcher(): void
    {
        $taskId = 1;
        $pageSize = 10;
        $format = 'json';
        $curPageCalls = [];
        $addDataCalls = [];

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $taskMock = $this->createMock(TaskInterface::class);
        $firstProductMock = $this->createMock(Product::class);
        $secondProductMock = $this->createMock(Product::class);

        $feedSpecificationMock->expects($this->any())
            ->method('getFormat')
            ->willReturn($format);

        $feedSpecificationMock->expects($this->once())
            ->method('getPreSignedUrl')
            ->willReturn('https://example.com/path/to/feed.json');

        $feedSpecificationMock->expects($this->once())
            ->method('setFormat')
            ->with('json')
            ->willReturnSelf();

        $this->storageMock->expects($this->once())
            ->method('isSupportedFormat')
            ->with($format)
            ->willReturn(true);

        $this->itemsGeneratorMock->expects($this->exactly(2))
            ->method('resetDataProviders')
            ->with($feedSpecificationMock);

        $this->contextManagerMock->expects($this->once())
            ->method('setContextFromSpecification')
            ->with($feedSpecificationMock);

        $this->storageMock->expects($this->once())
            ->method('initiate')
            ->with($feedSpecificationMock);

        $this->collectionProcessorMock->expects($this->once())
            ->method('getCollection')
            ->with($feedSpecificationMock)
            ->willReturn($collectionMock);

        $this->collectionConfigMock->expects($this->once())
            ->method('getPageSize')
            ->willReturn($pageSize);

        $collectionMock->expects($this->once())
            ->method('setPageSize')
            ->with($pageSize)
            ->willReturnSelf();

        $this->appConfigMock->expects($this->once())
            ->method('isDebug')
            ->willReturn(false);

        $collectionMock->expects($this->once())
            ->method('getLastPageNumber')
            ->willReturn(2);

        $this->appConfigMock->expects($this->once())
            ->method('getValue')
            ->with('product_metric_max_page')
            ->willReturn(10);

        $collectionMock->expects($this->exactly(2))
            ->method('setCurPage')
            ->willReturnCallback(function ($page) use (&$curPageCalls, $collectionMock) {
                $curPageCalls[] = $page;
                return $collectionMock;
            });

        $collectionMock->expects($this->exactly(2))
            ->method('load')
            ->willReturnSelf();

        $collectionMock->expects($this->any())
            ->method('getSelect')
            ->willReturn($selectMock);

        $selectMock->expects($this->any())
            ->method('__toString')
            ->willReturn('SELECT * FROM catalog_product_entity');

        $this->collectionProcessorMock->expects($this->exactly(2))
            ->method('processAfterLoad')
            ->with($collectionMock, $feedSpecificationMock);

        $collectionMock->expects($this->exactly(2))
            ->method('getItems')
            ->willReturnCallback(function () use (&$curPageCalls, $firstProductMock, $secondProductMock) {
                return end($curPageCalls) === 1
                    ? [$firstProductMock]
                    : [$secondProductMock];
            });

        $this->itemsGeneratorMock->expects($this->exactly(2))
            ->method('generate')
            ->willReturnCallback(function ($items, $feedSpecification) use (
                $feedSpecificationMock,
                $firstProductMock,
                $secondProductMock
            ) {
                $this->assertSame($feedSpecificationMock, $feedSpecification);

                if ($items === [$firstProductMock]) {
                    return [
                        [
                            'entity_id' => 1,
                            'sku' => 'product-1',
                        ],
                    ];
                }

                if ($items === [$secondProductMock]) {
                    return [
                        [
                            'entity_id' => 2,
                            'sku' => 'product-2',
                        ],
                    ];
                }

                $this->fail('Unexpected items passed to ItemsGenerator::generate().');
            });

        $this->storageMock->expects($this->exactly(2))
            ->method('addData')
            ->willReturnCallback(function ($items, $id) use (&$addDataCalls, $taskId) {
                $this->assertSame($taskId, $id);
                $addDataCalls[] = $items;
            });

        $this->itemsGeneratorMock->expects($this->exactly(2))
            ->method('resetDataProvidersAfterFetchItems')
            ->with($feedSpecificationMock);

        $collectionMock->expects($this->exactly(2))
            ->method('clear')
            ->willReturnSelf();

        $this->collectionProcessorMock->expects($this->exactly(2))
            ->method('processAfterFetchItems')
            ->with($collectionMock, $feedSpecificationMock);

        $this->taskRepositoryMock->expects($this->once())
            ->method('get')
            ->with($taskId)
            ->willReturn($taskMock);

        $taskMock->expects($this->once())
            ->method('setProductCount')
            ->with(2)
            ->willReturnSelf();

        $this->taskRepositoryMock->expects($this->once())
            ->method('save')
            ->with($taskMock)
            ->willReturn($taskMock);

        $this->storageMock->expects($this->once())
            ->method('commit')
            ->with($taskId);

        $this->metricCollectorMock->expects($this->once())
            ->method('reset')
            ->with(CollectorInterface::CODE_PRODUCT_FEED);

        $this->contextManagerMock->expects($this->once())
            ->method('resetContext');

        $this->generateFeed->execute($feedSpecificationMock, $taskId);

        $this->assertSame([1, 2], $curPageCalls);
        $this->assertSame(
            [
                [
                    [
                        'entity_id' => 1,
                        'sku' => 'product-1',
                    ],
                ],
                [
                    [
                        'entity_id' => 2,
                        'sku' => 'product-2',
                    ],
                ],
            ],
            $addDataCalls
        );
    }

    public function testExecuteRollsBackStorageWhenCollectionLoadFails(): void
    {
        $taskId = 1;
        $pageSize = 10;
        $format = 'json';

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $feedSpecificationMock->expects($this->any())
            ->method('getFormat')
            ->willReturn($format);

        $feedSpecificationMock->expects($this->once())
            ->method('getPreSignedUrl')
            ->willReturn('https://example.com/path/to/feed.json');

        $feedSpecificationMock->expects($this->once())
            ->method('setFormat')
            ->with('json')
            ->willReturnSelf();

        $this->storageMock->expects($this->once())
            ->method('isSupportedFormat')
            ->with($format)
            ->willReturn(true);

        $this->itemsGeneratorMock->expects($this->once())
            ->method('resetDataProviders')
            ->with($feedSpecificationMock);

        $this->contextManagerMock->expects($this->once())
            ->method('setContextFromSpecification')
            ->with($feedSpecificationMock);

        $this->storageMock->expects($this->once())
            ->method('initiate')
            ->with($feedSpecificationMock);

        $this->collectionProcessorMock->expects($this->once())
            ->method('getCollection')
            ->with($feedSpecificationMock)
            ->willReturn($collectionMock);

        $this->collectionConfigMock->expects($this->once())
            ->method('getPageSize')
            ->willReturn($pageSize);

        $collectionMock->expects($this->once())
            ->method('setPageSize')
            ->with($pageSize)
            ->willReturnSelf();

        $this->appConfigMock->expects($this->once())
            ->method('isDebug')
            ->willReturn(false);

        $collectionMock->expects($this->once())
            ->method('getLastPageNumber')
            ->willReturn(1);

        $this->appConfigMock->expects($this->once())
            ->method('getValue')
            ->with('product_metric_max_page')
            ->willReturn(10);

        $collectionMock->expects($this->once())
            ->method('setCurPage')
            ->with(1)
            ->willReturnSelf();

        $collectionMock->expects($this->once())
            ->method('load')
            ->willThrowException(new \Exception('Collection load failed'));

        $collectionMock->expects($this->any())
            ->method('getSelect')
            ->willReturn($selectMock);

        $selectMock->expects($this->any())
            ->method('__toString')
            ->willReturn('SELECT * FROM catalog_product_entity');

        $this->storageMock->expects($this->once())
            ->method('rollback');

        $this->storageMock->expects($this->never())
            ->method('addData');

        $this->storageMock->expects($this->never())
            ->method('commit');

        $this->taskRepositoryMock->expects($this->never())
            ->method('get');

        $this->taskRepositoryMock->expects($this->never())
            ->method('save');

        $this->contextManagerMock->expects($this->never())
            ->method('resetContext');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Collection load failed');

        $this->generateFeed->execute($feedSpecificationMock, $taskId);
    }

    public function testExecuteThrowsExceptionWhenPreSignedUrlHasNoPath(): void
    {
        $taskId = 1;
        $format = 'json';

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);

        $feedSpecificationMock->expects($this->any())
            ->method('getFormat')
            ->willReturn($format);

        $feedSpecificationMock->expects($this->once())
            ->method('getPreSignedUrl')
            ->willReturn('https://example.com');

        $feedSpecificationMock->expects($this->never())
            ->method('setFormat');

        $this->storageMock->expects($this->never())
            ->method('isSupportedFormat');

        $this->storageMock->expects($this->never())
            ->method('initiate');

        $this->collectionProcessorMock->expects($this->never())
            ->method('getCollection');

        $this->storageMock->expects($this->never())
            ->method('rollback');

        $this->storageMock->expects($this->never())
            ->method('commit');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Not able to set the format');

        $this->generateFeed->execute($feedSpecificationMock, $taskId);
    }

    public function testExecuteThrowsExceptionWhenFormatIsUnsupported(): void
    {
        $taskId = 1;
        $format = 'xml';

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);

        $feedSpecificationMock->expects($this->any())
            ->method('getFormat')
            ->willReturn($format);

        $feedSpecificationMock->expects($this->once())
            ->method('getPreSignedUrl')
            ->willReturn('https://example.com/path/to/feed.xml');

        $feedSpecificationMock->expects($this->once())
            ->method('setFormat')
            ->with('xml')
            ->willReturnSelf();

        $this->storageMock->expects($this->once())
            ->method('isSupportedFormat')
            ->with($format)
            ->willReturn(false);

        $this->storageMock->expects($this->never())
            ->method('initiate');

        $this->collectionProcessorMock->expects($this->never())
            ->method('getCollection');

        $this->storageMock->expects($this->never())
            ->method('rollback');

        $this->storageMock->expects($this->never())
            ->method('commit');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('xml is not supported format');

        $this->generateFeed->execute($feedSpecificationMock, $taskId);
    }
}
