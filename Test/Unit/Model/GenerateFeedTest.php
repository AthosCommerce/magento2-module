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

use Magento\Catalog\Model\Product;
use AthosCommerce\Feed\Api\AppConfigInterface;
use AthosCommerce\Feed\Model\Feed\Collection\ProcessCollectionInterface;
use AthosCommerce\Feed\Model\Feed\Collection\ProcessorPool;
use AthosCommerce\Feed\Model\Feed\CollectionConfigInterface;
use AthosCommerce\Feed\Model\Feed\CollectionProviderInterface;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use AthosCommerce\Feed\Model\Feed\DataProviderPool;
use AthosCommerce\Feed\Model\Feed\Specification\Feed;
use AthosCommerce\Feed\Model\Feed\StorageInterface;
use AthosCommerce\Feed\Model\Feed\SystemFieldsList;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use AthosCommerce\Feed\Model\Metric\CollectorInterface;

class GenerateFeedTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CollectionProviderInterface
     */
    private $collectionProviderMock;

    /**
     * @var DataProviderPool
     */
    private $dataProviderPoolMock;

    /**
     * @var CollectionConfigInterface
     */
    private $collectionConfigMock;

    /**
     * @var StorageInterface
     */
    private $storageMock;

    /**
     * @var SystemFieldsList
     */
    private $systemFieldsListMock;

    /**
     *
     * @var ContextManagerInterface
     */
    private $contextManagerMock;

    /**
     * @var ProcessorPool
     */
    private $afterLoadProcessorPoolMock;

    private $appConfigMock;

    private $generateFeed;
    private $metricCollectorMock;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->collectionProviderMock = $this->createMock(CollectionProviderInterface::class);
        $this->dataProviderPoolMock = $this->createMock(DataProviderPool::class);
        $this->collectionConfigMock = $this->createMock(CollectionConfigInterface::class);
        $this->storageMock = $this->createMock(StorageInterface::class);
        $this->systemFieldsListMock = $this->createMock(SystemFieldsList::class);
        $this->contextManagerMock = $this->createMock(ContextManagerInterface::class);
        $this->afterLoadProcessorPoolMock = $this->createMock(ProcessorPool::class);
        $this->metricCollectorMock = $this->createMock(CollectorInterface::class);
        $this->appConfigMock = $this->createMock(AppConfigInterface::class);
        $this->generateFeed = new \AthosCommerce\Feed\Model\GenerateFeed(
            $this->collectionProviderMock,
            $this->dataProviderPoolMock,
            $this->collectionConfigMock,
            $this->storageMock,
            $this->systemFieldsListMock,
            $this->contextManagerMock,
            $this->afterLoadProcessorPoolMock,
            $this->metricCollectorMock,
            $this->appConfigMock
        );
    }

    public function testExecute()
    {
        $pageSize = 10;
        $format = 'format';

        $dataProviderMock = $this->createMock(DataProviderInterface::class);
        $dataProviderMockSecond = $this->createMock(DataProviderInterface::class);
        $processCollectionInterfaceMock = $this->createMock(ProcessCollectionInterface::class);
        $processCollectionInterfaceMockSecond = $this->createMock(ProcessCollectionInterface::class);
        $collectionMock = $this->getMockBuilder(Collection::class)->disableOriginalConstructor()->getMock();
        $feedSpecificationMock = $this->getMockBuilder(Feed::class)->disableOriginalConstructor()->getMock();
        $productMock = $this->createMock(Product::class);
        $productMockSecond = $this->createMock(Product::class);
        $dataProviders = [
            $dataProviderMock,
            $dataProviderMockSecond,
        ];
        // Configure the feedSpecificationMock to return a valid URL
        $feedSpecificationMock->expects($this->once())
            ->method('getPreSignedUrl')
            ->willReturn('https://example.com/path/to/file.json.gz'); // Return a valid URL

        $feedSpecificationMock->expects($this->once())
            ->method('getFormat')
            ->willReturn($format);
        $this->storageMock->expects($this->once())
            ->method('isSupportedFormat')
            ->with($format)
            ->willReturn(true);
        $this->storageMock->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn(
                [
                    'name' => 'test',
                    'size' => 333
                ]
            );
        $this->metricCollectorMock->expects($this->any())
            ->method('collect')
            ->withAnyParameters();
        $this->metricCollectorMock->expects($this->any())
            ->method('print')
            ->withAnyParameters();
        $dataProviderMock->expects($this->exactly(2))
            ->method('reset');
        $dataProviderMockSecond->expects($this->exactly(2))
            ->method('reset');
        $this->contextManagerMock->expects($this->once())
            ->method('setContextFromSpecification')
            ->with($feedSpecificationMock);
        $this->storageMock->expects($this->once())
            ->method('initiate')
            ->with($feedSpecificationMock);
        $this->collectionProviderMock->expects($this->once())
            ->method('getCollection')
            ->willReturn($collectionMock);
        $this->collectionConfigMock->expects($this->once())
            ->method('getPageSize')
            ->willReturn($pageSize);
        $collectionMock->expects($this->once())
            ->method('setPageSize')
            ->with($pageSize);
        $collectionMock->expects($this->once())
            ->method('getLastPageNumber')
            ->willReturn(2);
        $this->appConfigMock->expects($this->once())
            ->method('getValue')
            ->with('product_metric_max_page')
            ->willReturn(10);
        $collectionMock->expects($this->at(2))
            ->method('setCurPage')
            ->with(1);
        $collectionMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $this->afterLoadProcessorPoolMock->expects($this->any())
            ->method('getAll')
            ->willReturn([$processCollectionInterfaceMock, $processCollectionInterfaceMockSecond]);
        $processCollectionInterfaceMock->expects($this->any())
            ->method('processAfterLoad')
            ->with($collectionMock, $feedSpecificationMock);
        $processCollectionInterfaceMockSecond->expects($this->any())
            ->method('processAfterLoad')
            ->with($collectionMock, $feedSpecificationMock);
        $this->contextManagerMock->expects($this->once())
            ->method('resetContext');
        $collectionMock->expects($this->at(4))
            ->method('getItems')
            ->willReturn([$productMock]);
        $productMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(1);
        $this->systemFieldsListMock->expects($this->any())
            ->method('add')
            ->with('product_model');
        $feedSpecificationMock->expects($this->any())
            ->method('getIgnoreFields')
            ->willReturn(['test']);
        $this->dataProviderPoolMock->expects($this->any())
            ->method('get')
            ->with(['test'])
            ->willReturn($dataProviders);
        $dataProviderMock->expects($this->at(1))
            ->method('getData')
            ->with(
                [
                    [
                        'entity_id' => 1,
                        'product_model' => $productMock
                    ],
                ],
                $feedSpecificationMock
            )->willReturn([
                [
                    'entity_id' => 1,
                    'product_model' => $productMock,
                    'data_provider_1' => 'value_1',
                ],
            ]);
        $dataProviderMockSecond->expects($this->at(1))
            ->method('getData')
            ->with(
                [
                    [
                        'entity_id' => 1,
                        'product_model' => $productMock,
                        'data_provider_1' => 'value_1',
                    ],
                ],
                $feedSpecificationMock
            )->willReturn(
                [
                    [
                        'entity_id' => 1,
                        'product_model' => $productMock,
                        'data_provider_1' => 'value_1',
                        'data_provider_2' => 'value_3',
                    ],
                ]
            );
        $productMock->expects($this->once())
            ->method('__sleep')
            ->willReturn([]);
        $this->storageMock->expects($this->at(5))
            ->method('addData')
            ->with(
                [
                    [
                        'entity_id' => 1,
                        'product_model' => $productMockSecond,
                        'data_provider_1' => 'value_1',
                        'data_provider_2' => 'value_3',
                    ]
                ]
            );
        $dataProviderMock->expects($this->any())
            ->method('resetAfterFetchItems');
        $dataProviderMockSecond->expects($this->any())
            ->method('resetAfterFetchItems');
        $collectionMock->expects($this->exactly(2))
            ->method('clear');
        $processCollectionInterfaceMock->expects($this->any())
            ->method('processAfterFetchItems')
            ->with($collectionMock, $feedSpecificationMock);
        $processCollectionInterfaceMockSecond->expects($this->any())
            ->method('processAfterFetchItems')
            ->with($collectionMock, $feedSpecificationMock);
        $collectionMock->expects($this->at(6))
            ->method('setCurPage')
            ->with(2);
        $collectionMock->expects($this->at(8))
            ->method('getItems')
            ->willReturn([$productMockSecond]);
        $productMockSecond->expects($this->any())
            ->method('getEntityId')
            ->willReturn(2);
        $dataProviderMock->expects($this->at(3))
            ->method('getData')
            ->with(
                [
                    [
                        'entity_id' => 2,
                        'product_model' => $productMockSecond
                    ]
                ],
                $feedSpecificationMock
            )->willReturn(
                [
                    [
                        'entity_id' => 2,
                        'product_model' => $productMockSecond,
                        'data_provider_1' => 'value_2',
                    ]
                ]
            );
        $dataProviderMockSecond->expects($this->at(3))
            ->method('getData')
            ->with(
                [
                    [
                        'entity_id' => 2,
                        'product_model' => $productMockSecond,
                        'data_provider_1' => 'value_2',
                    ]
                ],
                $feedSpecificationMock
            )->willReturn(
                [
                    [
                        'entity_id' => 2,
                        'product_model' => $productMockSecond,
                        'data_provider_1' => 'value_2',
                        'data_provider_2' => 'value_4',
                    ]
                ]
            );
        $productMockSecond->expects($this->once())
            ->method('__sleep')
            ->willReturn([]);
        $this->storageMock->expects($this->at(7))
            ->method('addData')
            ->with(
                [
                    [
                        'entity_id' => 2,
                        'product_model' => $productMockSecond,
                        'data_provider_1' => 'value_2',
                        'data_provider_2' => 'value_4',
                    ]
                ]
            );
        $this->storageMock->expects($this->once())
            ->method('commit');
        $this->metricCollectorMock->expects($this->once())
            ->method('reset')
            ->with(CollectorInterface::CODE_PRODUCT_FEED);
        $this->contextManagerMock->expects($this->once())
            ->method('resetContext');
        $this->generateFeed->execute($feedSpecificationMock, 1);
    }

    public function testExecuteExceptionCase()
    {
        $pageSize = 10;
        $format = 'format';

        $collectionMock = $this->getMockBuilder(Collection::class)->disableOriginalConstructor()->getMock();
        $feedSpecificationMock = $this->getMockBuilder(Feed::class)->disableOriginalConstructor()->getMock();
        $feedSpecificationMock->expects($this->once())
            ->method('getFormat')
            ->willReturn($format);
        // Configure getPreSignedUrl() to return a valid URL
        $feedSpecificationMock->expects($this->once())
            ->method('getPreSignedUrl')
            ->willReturn('https://example.com/path/to/file.json.gz');
        $this->storageMock->expects($this->once())
            ->method('isSupportedFormat')
            ->with($format)
            ->willReturn(true);
        $this->storageMock->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn(
                [
                    'name' => 'test',
                    'size' => 333
                ]
            );
        $this->metricCollectorMock->expects($this->any())
            ->method('collect')
            ->withAnyParameters();
        $this->metricCollectorMock->expects($this->any())
            ->method('print')
            ->withAnyParameters();
        $this->contextManagerMock->expects($this->once())
            ->method('setContextFromSpecification')
            ->with($feedSpecificationMock);
        $this->storageMock->expects($this->once())
            ->method('initiate')
            ->with($feedSpecificationMock);
        $this->collectionProviderMock->expects($this->once())
            ->method('getCollection')
            ->willReturn($collectionMock);
        $this->collectionConfigMock->expects($this->once())
            ->method('getPageSize')
            ->willReturn($pageSize);
        $collectionMock->expects($this->once())
            ->method('setPageSize')
            ->with($pageSize);
        $collectionMock->expects($this->once())
            ->method('getLastPageNumber')
            ->willReturn(2);
        $this->appConfigMock->expects($this->once())
            ->method('getValue')
            ->with('product_metric_max_page')
            ->willReturn(10);
        $collectionMock->expects($this->at(2))
            ->method('setCurPage')
            ->willThrowException(new \Exception());
        $this->storageMock->expects($this->once())
            ->method('rollback');
        $this->expectException(\Exception::class);
        $this->generateFeed->execute($feedSpecificationMock,1);
    }

    public function testExecuteExceptionCaseOnUnsupportedFormat()
    {
        $format = 'format';
        $feedSpecificationMock = $this->getMockBuilder(Feed::class)->disableOriginalConstructor()->getMock();
        $feedSpecificationMock->expects($this->once())
            ->method('getFormat')
            ->willReturn($format);
        // Configure getPreSignedUrl() to return a valid URL
        $feedSpecificationMock->expects($this->once())
            ->method('getPreSignedUrl')
            ->willReturn('https://example.com/path/to/file.json.gz');
        $this->storageMock->expects($this->once())
            ->method('isSupportedFormat')
            ->with($format)
            ->willReturn(false);
        $this->expectExceptionMessage('format is not supported format');
        $this->expectException(\Exception::class);
        $this->generateFeed->execute($feedSpecificationMock,1);
    }
}
