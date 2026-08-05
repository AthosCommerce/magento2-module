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

namespace AthosCommerce\Feed\Test\Unit\Model;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\CollectionProcessor;
use AthosCommerce\Feed\Model\Feed\Collection\ProcessCollectionInterface;
use AthosCommerce\Feed\Model\Feed\Collection\ProcessorPool;
use AthosCommerce\Feed\Model\Feed\CollectionConfigInterface;
use AthosCommerce\Feed\Model\Feed\CollectionProviderInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CollectionProcessorTest extends TestCase
{
    /**
     * @var CollectionProviderInterface|MockObject
     */
    private $collectionProviderMock;

    /**
     * @var ProcessorPool|MockObject
     */
    private $processorPoolMock;

    /**
     * @var CollectionConfigInterface|MockObject
     */
    private $collectionConfigMock;

    /**
     * @var AthosCommerceLogger|MockObject
     */
    private $loggerMock;

    /**
     * @var CollectionProcessor
     */
    private $collectionProcessor;

    protected function setUp(): void
    {
        $this->collectionProviderMock = $this->createMock(CollectionProviderInterface::class);
        $this->processorPoolMock = $this->createMock(ProcessorPool::class);
        $this->collectionConfigMock = $this->createMock(CollectionConfigInterface::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->collectionProcessor = new CollectionProcessor(
            $this->collectionProviderMock,
            $this->processorPoolMock,
            $this->collectionConfigMock,
            $this->loggerMock
        );
    }

    public function testGetCollectionSetsPageSizeAndReturnsCollection(): void
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $collectionMock = $this->createMock(Collection::class);

        $this->collectionProviderMock->expects($this->once())
            ->method('getCollection')
            ->with($feedSpecificationMock)
            ->willReturn($collectionMock);

        $this->collectionConfigMock->expects($this->once())
            ->method('getPageSize')
            ->willReturn(100);

        $collectionMock->expects($this->once())
            ->method('setPageSize')
            ->with(100)
            ->willReturnSelf();

        $this->assertSame(
            $collectionMock,
            $this->collectionProcessor->getCollection($feedSpecificationMock)
        );
    }

    public function testProcessAfterLoadCallsAllProcessors(): void
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $collectionMock = $this->createMock(Collection::class);

        $processorOneMock = $this->createMock(ProcessCollectionInterface::class);
        $processorTwoMock = $this->createMock(ProcessCollectionInterface::class);

        $this->processorPoolMock->expects($this->once())
            ->method('getAll')
            ->willReturn([$processorOneMock, $processorTwoMock]);

        $processorOneMock->expects($this->once())
            ->method('processAfterLoad')
            ->with($collectionMock, $feedSpecificationMock)
            ->willReturn($collectionMock);

        $processorTwoMock->expects($this->once())
            ->method('processAfterLoad')
            ->with($collectionMock, $feedSpecificationMock)
            ->willReturn($collectionMock);

        $this->collectionProcessor->processAfterLoad($collectionMock, $feedSpecificationMock);
    }

    public function testProcessAfterFetchItemsCallsAllProcessors(): void
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $collectionMock = $this->createMock(Collection::class);

        $processorOneMock = $this->createMock(ProcessCollectionInterface::class);
        $processorTwoMock = $this->createMock(ProcessCollectionInterface::class);

        $this->processorPoolMock->expects($this->once())
            ->method('getAll')
            ->willReturn([$processorOneMock, $processorTwoMock]);

        $processorOneMock->expects($this->once())
            ->method('processAfterFetchItems')
            ->with($collectionMock, $feedSpecificationMock)
            ->willReturn($collectionMock);

        $processorTwoMock->expects($this->once())
            ->method('processAfterFetchItems')
            ->with($collectionMock, $feedSpecificationMock)
            ->willReturn($collectionMock);

        $this->collectionProcessor->processAfterFetchItems($collectionMock, $feedSpecificationMock);
    }

    public function testProcessAfterLoadWithEmptyProcessorPool(): void
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $collectionMock = $this->createMock(Collection::class);

        $this->processorPoolMock->expects($this->once())
            ->method('getAll')
            ->willReturn([]);

        $this->collectionProcessor->processAfterLoad($collectionMock, $feedSpecificationMock);

        $this->addToAssertionCount(1);
    }

    public function testProcessAfterFetchItemsWithEmptyProcessorPool(): void
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $collectionMock = $this->createMock(Collection::class);

        $this->processorPoolMock->expects($this->once())
            ->method('getAll')
            ->willReturn([]);

        $this->collectionProcessor->processAfterFetchItems($collectionMock, $feedSpecificationMock);

        $this->addToAssertionCount(1);
    }
}
