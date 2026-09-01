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

namespace AthosCommerce\Feed\Test\Unit\Model\Api;

require_once dirname(__DIR__, 2) . '/_files/bootstrap-stubs.php';

use AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface;
use AthosCommerce\Feed\Api\Data\EntityTrackingItemInterfaceFactory;
use AthosCommerce\Feed\Api\Data\EntityTrackingListResponseInterface;
use AthosCommerce\Feed\Api\Data\EntityTrackingListResponseInterfaceFactory;
use AthosCommerce\Feed\Api\Data\IndexingEntityInterface;
use AthosCommerce\Feed\Api\Data\IndexingEntitySearchResultsInterface;
use AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface;
use AthosCommerce\Feed\Model\Api\EntityTrackingStatusResolver;
use AthosCommerce\Feed\Model\Api\GetEntityList;
use AthosCommerce\Feed\Model\IndexingEntity;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\SortOrderBuilderFactory;
use PHPUnit\Framework\TestCase;

class GetEntityListTest extends TestCase
{
    /**
     * @var IndexingEntityRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $repositoryMock;

    /**
     * @var SearchCriteriaBuilderFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $searchCriteriaBuilderFactoryMock;

    /**
     * @var SortOrderBuilderFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sortOrderBuilderFactoryMock;

    /**
     * @var EntityTrackingListResponseInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $responseFactoryMock;

    /**
     * @var EntityTrackingItemInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $itemFactoryMock;

    /**
     * @var EntityTrackingStatusResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    private $statusResolverMock;

    /**
     * @var GetEntityList
     */
    private $model;

    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(IndexingEntityRepositoryInterface::class);
        $this->searchCriteriaBuilderFactoryMock = $this->createMock(SearchCriteriaBuilderFactory::class);
        $this->sortOrderBuilderFactoryMock = $this->createMock(SortOrderBuilderFactory::class);
        $this->responseFactoryMock = $this->createMock(EntityTrackingListResponseInterfaceFactory::class);
        $this->itemFactoryMock = $this->createMock(EntityTrackingItemInterfaceFactory::class);
        $this->statusResolverMock = $this->createMock(EntityTrackingStatusResolver::class);

        $this->model = new GetEntityList(
            $this->repositoryMock,
            $this->searchCriteriaBuilderFactoryMock,
            $this->sortOrderBuilderFactoryMock,
            $this->responseFactoryMock,
            $this->itemFactoryMock,
            $this->statusResolverMock
        );
    }

    public function testGetListAppliesFiltersAndMapsData(): void
    {
        $searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $sortOrderBuilder = $this->createMock(SortOrderBuilder::class);
        $sortOrder = $this->createMock(SortOrder::class);
        $searchResults = $this->createMock(IndexingEntitySearchResultsInterface::class);
        $response = $this->createMock(EntityTrackingListResponseInterface::class);
        $item = $this->createMock(EntityTrackingItemInterface::class);
        $entity = $this->createMock(IndexingEntity::class);

        $this->searchCriteriaBuilderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaBuilder);

        $searchCriteriaBuilder->expects($this->once())->method('setCurrentPage')->with(2);
        $searchCriteriaBuilder->expects($this->once())->method('setPageSize')->with(10);
        $searchCriteriaBuilder->expects($this->atLeastOnce())->method('addFilter');
        $searchCriteriaBuilder->expects($this->once())->method('addSortOrder')->with($sortOrder);
        $searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);

        $this->sortOrderBuilderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($sortOrderBuilder);

        $sortOrderBuilder->expects($this->once())->method('setField')->with(IndexingEntity::UPDATED_AT)->willReturnSelf();
        $sortOrderBuilder->expects($this->once())->method('setDirection')->with(SortOrder::SORT_DESC)->willReturnSelf();
        $sortOrderBuilder->expects($this->once())->method('create')->willReturn($sortOrder);

        $this->repositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteria, true)
            ->willReturn($searchResults);

        $searchResults->expects($this->once())->method('getItems')->willReturn([$entity]);
        $searchResults->expects($this->once())->method('getTotalCount')->willReturn(1);

        $this->statusResolverMock->expects($this->once())
            ->method('resolve')
            ->with($entity)
            ->willReturn(EntityTrackingStatusResolver::STATUS_PENDING);

        $this->itemFactoryMock->expects($this->once())->method('create')->willReturn($item);

        $entityTypeInput = 'product';
        $entityType = '__PRODUCT';
        $entity->expects($this->once())->method('getId')->willReturn(99);
        $entity->expects($this->once())->method('getTargetId')->willReturn(123);
        $entity->expects($this->once())->method('getTargetEntityType')->willReturn($entityType);
        $entity->expects($this->once())->method('getTargetEntitySubtype')->willReturn('simple');
        $entity->expects($this->once())->method('getTargetParentId')->willReturn(456);
        $entity->expects($this->once())->method('getSiteId')->willReturn('0');
        $entity->expects($this->once())->method('getNextAction')->willReturn('sync');
        $entity->expects($this->once())->method('getLastAction')->willReturn('sync');
        $entity->expects($this->once())->method('getLastActionTimestamp')->willReturn('2025-01-01 00:00:00');
        $entity->expects($this->once())->method('getLockTimestamp')->willReturn(null);
        $entity->expects($this->once())->method('getIsIndexable')->willReturn(true);
        $entity->expects($this->once())->method('getCreatedAt')->willReturn('2025-01-01 00:00:00');
        $entity->expects($this->once())->method('getUpdatedAt')->willReturn('2025-01-01 00:05:00');

        $item->expects($this->once())->method('setEntityId')->with(99)->willReturnSelf();
        $item->expects($this->once())->method('setTargetId')->with(123)->willReturnSelf();
        $item->expects($this->once())->method('setEntityType')->with($entityType)->willReturnSelf();
        $item->expects($this->once())->method('setTargetEntitySubtype')->with('simple')->willReturnSelf();
        $item->expects($this->once())->method('setTargetParentId')->with(456)->willReturnSelf();
        $item->expects($this->once())->method('setSiteId')->with('0')->willReturnSelf();
        $item->expects($this->once())->method('setStatus')->with(EntityTrackingStatusResolver::STATUS_PENDING)->willReturnSelf();
        $item->expects($this->once())->method('setNextAction')->with('sync')->willReturnSelf();
        $item->expects($this->once())->method('setLastAction')->with('sync')->willReturnSelf();
        $item->expects($this->once())->method('setLastActionTimestamp')->with('2025-01-01 00:00:00')->willReturnSelf();
        $item->expects($this->once())->method('setLockTimestamp')->with(null)->willReturnSelf();
        $item->expects($this->once())->method('setIsIndexable')->with(true)->willReturnSelf();
        $item->expects($this->once())->method('setLastApiResponse')->with(null)->willReturnSelf();
        $item->expects($this->once())->method('setCreatedAt')->with('2025-01-01 00:00:00')->willReturnSelf();
        $item->expects($this->once())->method('setUpdatedAt')->with('2025-01-01 00:05:00')->willReturnSelf();

        $this->responseFactoryMock->expects($this->once())->method('create')->willReturn($response);
        $response->expects($this->once())->method('setItems')->with([$item])->willReturnSelf();
        $response->expects($this->once())->method('setTotal')->with(1)->willReturnSelf();
        $response->expects($this->once())->method('setCurrentPage')->with(2)->willReturnSelf();
        $response->expects($this->once())->method('setPageSize')->with(10)->willReturnSelf();

        $result = $this->model->getList(2, 10, 123, 456, $entityTypeInput, '0', 'pending');

        $this->assertSame($response, $result);
    }

    public function testGetListIgnoresZeroTargetParentIdFilter(): void
    {
        $searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $sortOrderBuilder = $this->createMock(SortOrderBuilder::class);
        $sortOrder = $this->createMock(SortOrder::class);
        $searchResults = $this->createMock(IndexingEntitySearchResultsInterface::class);
        $response = $this->createMock(EntityTrackingListResponseInterface::class);
        $filterCalls = [];

        $this->searchCriteriaBuilderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaBuilder);

        $searchCriteriaBuilder->method('setCurrentPage')->willReturnSelf();
        $searchCriteriaBuilder->method('setPageSize')->willReturnSelf();
        $searchCriteriaBuilder->method('addFilter')
            ->willReturnCallback(function (...$args) use (&$filterCalls, $searchCriteriaBuilder) {
                $filterCalls[] = $args;
                return $searchCriteriaBuilder;
            });
        $searchCriteriaBuilder->method('addSortOrder')->willReturnSelf();
        $searchCriteriaBuilder->method('create')->willReturn($searchCriteria);

        $this->sortOrderBuilderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($sortOrderBuilder);
        $sortOrderBuilder->method('setField')->willReturnSelf();
        $sortOrderBuilder->method('setDirection')->willReturnSelf();
        $sortOrderBuilder->method('create')->willReturn($sortOrder);

        $this->repositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteria, true)
            ->willReturn($searchResults);

        $searchResults->method('getItems')->willReturn([]);
        $searchResults->method('getTotalCount')->willReturn(0);

        $this->responseFactoryMock->expects($this->once())->method('create')->willReturn($response);
        $response->method('setItems')->willReturnSelf();
        $response->method('setTotal')->willReturnSelf();
        $response->method('setCurrentPage')->willReturnSelf();
        $response->method('setPageSize')->willReturnSelf();

        $this->model->getList(1, 20, null, 0, null, null, null);

        foreach ($filterCalls as $filterCall) {
            $this->assertNotSame(IndexingEntity::TARGET_PARENT_ID, $filterCall[0]);
        }
    }
}
