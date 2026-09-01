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
use AthosCommerce\Feed\Api\Data\IndexingEntityInterface;
use AthosCommerce\Feed\Api\Data\IndexingEntitySearchResultsInterface;
use AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface;
use AthosCommerce\Feed\Model\Api\EntityTrackingStatusResolver;
use AthosCommerce\Feed\Model\Api\GetEntityDetail;
use AthosCommerce\Feed\Model\IndexingEntity;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\SortOrderBuilderFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;

class GetEntityDetailTest extends TestCase
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
     * @var EntityTrackingItemInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $itemFactoryMock;

    /**
     * @var EntityTrackingStatusResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    private $statusResolverMock;

    /**
     * @var GetEntityDetail
     */
    private $model;

    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(IndexingEntityRepositoryInterface::class);
        $this->searchCriteriaBuilderFactoryMock = $this->createMock(SearchCriteriaBuilderFactory::class);
        $this->sortOrderBuilderFactoryMock = $this->createMock(SortOrderBuilderFactory::class);
        $this->itemFactoryMock = $this->createMock(EntityTrackingItemInterfaceFactory::class);
        $this->statusResolverMock = $this->createMock(EntityTrackingStatusResolver::class);

        $this->model = new GetEntityDetail(
            $this->repositoryMock,
            $this->searchCriteriaBuilderFactoryMock,
            $this->sortOrderBuilderFactoryMock,
            $this->itemFactoryMock,
            $this->statusResolverMock
        );
    }

    public function testGetReturnsLatestTrackedEntity(): void
    {
        $searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $sortOrderBuilder = $this->createMock(SortOrderBuilder::class);
        $sortOrder = $this->createMock(SortOrder::class);
        $searchResults = $this->createMock(IndexingEntitySearchResultsInterface::class);
        $entity = $this->createMock(IndexingEntity::class);
        $item = $this->createMock(EntityTrackingItemInterface::class);

        $this->searchCriteriaBuilderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaBuilder);

        $filterCalls = [];
        $searchCriteriaBuilder->method('addFilter')->willReturnCallback(function (...$args) use (&$filterCalls) {
            $filterCalls[] = $args;
            return $this;
        });
        $searchCriteriaBuilder->expects($this->once())->method('setCurrentPage')->with(1);
        $searchCriteriaBuilder->expects($this->once())->method('setPageSize')->with(1);
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
            ->with($searchCriteria)
            ->willReturn($searchResults);

        $searchResults->expects($this->once())->method('getItems')->willReturn([$entity]);

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
        $entity->expects($this->once())->method('getLockTimestamp')->willReturn('2025-01-01 00:00:30');
        $entity->expects($this->once())->method('getIsIndexable')->willReturn(true);
        $entity->expects($this->once())->method('getCreatedAt')->willReturn('2025-01-01 00:00:00');
        $entity->expects($this->once())->method('getUpdatedAt')->willReturn('2025-01-01 00:05:00');

        $this->statusResolverMock->expects($this->once())
            ->method('resolve')
            ->with($entity)
            ->willReturn(EntityTrackingStatusResolver::STATUS_PROCESSING);

        $this->itemFactoryMock->expects($this->once())->method('create')->willReturn($item);

        $item->expects($this->once())->method('setEntityId')->with(99)->willReturnSelf();
        $item->expects($this->once())->method('setTargetId')->with(123)->willReturnSelf();
        $item->expects($this->once())->method('setEntityType')->with($entityType)->willReturnSelf();
        $item->expects($this->once())->method('setTargetEntitySubtype')->with('simple')->willReturnSelf();
        $item->expects($this->once())->method('setTargetParentId')->with(456)->willReturnSelf();
        $item->expects($this->once())->method('setSiteId')->with('0')->willReturnSelf();
        $item->expects($this->once())->method('setStatus')->with(EntityTrackingStatusResolver::STATUS_PROCESSING)->willReturnSelf();
        $item->expects($this->once())->method('setNextAction')->with('sync')->willReturnSelf();
        $item->expects($this->once())->method('setLastAction')->with('sync')->willReturnSelf();
        $item->expects($this->once())->method('setLastActionTimestamp')->with('2025-01-01 00:00:00')->willReturnSelf();
        $item->expects($this->once())->method('setLockTimestamp')->with('2025-01-01 00:00:30')->willReturnSelf();
        $item->expects($this->once())->method('setIsIndexable')->with(true)->willReturnSelf();
        $item->expects($this->once())->method('setLastApiResponse')->with(null)->willReturnSelf();
        $item->expects($this->once())->method('setCreatedAt')->with('2025-01-01 00:00:00')->willReturnSelf();
        $item->expects($this->once())->method('setUpdatedAt')->with('2025-01-01 00:05:00')->willReturnSelf();

        $result = $this->model->get($entityTypeInput, 123, 456);

        $this->assertContains([IndexingEntity::TARGET_ENTITY_TYPE, $entityType, 'eq'], $filterCalls);
        $this->assertContains([IndexingEntity::TARGET_ID, 123, 'eq'], $filterCalls);
        $this->assertContains([IndexingEntity::TARGET_PARENT_ID, 456, 'eq'], $filterCalls);
        $this->assertSame($item, $result);
    }

    public function testGetNormalizesNonProductEntityTypeBeforeQuerying(): void
    {
        $searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $sortOrderBuilder = $this->createMock(SortOrderBuilder::class);
        $sortOrder = $this->createMock(SortOrder::class);
        $searchResults = $this->createMock(IndexingEntitySearchResultsInterface::class);
        $entity = $this->createMock(IndexingEntity::class);
        $item = $this->createMock(EntityTrackingItemInterface::class);

        $this->searchCriteriaBuilderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaBuilder);

        $filterCalls = [];
        $searchCriteriaBuilder->method('addFilter')->willReturnCallback(function (...$args) use (&$filterCalls) {
            $filterCalls[] = $args;
            return $this;
        });
        $searchCriteriaBuilder->expects($this->once())->method('setCurrentPage')->with(1);
        $searchCriteriaBuilder->expects($this->once())->method('setPageSize')->with(1);
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
            ->with($searchCriteria)
            ->willReturn($searchResults);

        $searchResults->expects($this->once())->method('getItems')->willReturn([$entity]);

        $entity->expects($this->once())->method('getId')->willReturn(44);
        $entity->expects($this->once())->method('getTargetId')->willReturn(77);
        $entity->expects($this->once())->method('getTargetEntityType')->willReturn('__RECIPE');
        $entity->expects($this->once())->method('getTargetEntitySubtype')->willReturn(null);
        $entity->expects($this->once())->method('getTargetParentId')->willReturn(null);
        $entity->expects($this->once())->method('getSiteId')->willReturn('0');
        $entity->expects($this->once())->method('getNextAction')->willReturn('no_action');
        $entity->expects($this->once())->method('getLastAction')->willReturn('no_action');
        $entity->expects($this->once())->method('getLastActionTimestamp')->willReturn('2025-01-01 00:00:00');
        $entity->expects($this->once())->method('getLockTimestamp')->willReturn(null);
        $entity->expects($this->once())->method('getIsIndexable')->willReturn(true);
        $entity->expects($this->once())->method('getCreatedAt')->willReturn('2025-01-01 00:00:00');
        $entity->expects($this->once())->method('getUpdatedAt')->willReturn('2025-01-01 00:05:00');

        $this->statusResolverMock->expects($this->once())
            ->method('resolve')
            ->with($entity)
            ->willReturn(EntityTrackingStatusResolver::STATUS_FAILED);

        $this->itemFactoryMock->expects($this->once())->method('create')->willReturn($item);

        $item->expects($this->once())->method('setEntityId')->with(44)->willReturnSelf();
        $item->expects($this->once())->method('setTargetId')->with(77)->willReturnSelf();
        $item->expects($this->once())->method('setEntityType')->with('__RECIPE')->willReturnSelf();
        $item->expects($this->once())->method('setTargetEntitySubtype')->with(null)->willReturnSelf();
        $item->expects($this->once())->method('setTargetParentId')->with(null)->willReturnSelf();
        $item->expects($this->once())->method('setSiteId')->with('0')->willReturnSelf();
        $item->expects($this->once())->method('setStatus')->with(EntityTrackingStatusResolver::STATUS_FAILED)->willReturnSelf();
        $item->expects($this->once())->method('setNextAction')->with('no_action')->willReturnSelf();
        $item->expects($this->once())->method('setLastAction')->with('no_action')->willReturnSelf();
        $item->expects($this->once())->method('setLastActionTimestamp')->with('2025-01-01 00:00:00')->willReturnSelf();
        $item->expects($this->once())->method('setLockTimestamp')->with(null)->willReturnSelf();
        $item->expects($this->once())->method('setIsIndexable')->with(true)->willReturnSelf();
        $item->expects($this->once())->method('setLastApiResponse')->with(null)->willReturnSelf();
        $item->expects($this->once())->method('setCreatedAt')->with('2025-01-01 00:00:00')->willReturnSelf();
        $item->expects($this->once())->method('setUpdatedAt')->with('2025-01-01 00:05:00')->willReturnSelf();

        $result = $this->model->get('recipe', 77);

        $this->assertContains([IndexingEntity::TARGET_ENTITY_TYPE, '__RECIPE', 'eq'], $filterCalls);
        $this->assertSame($item, $result);
    }

    public function testGetThrowsNoSuchEntityExceptionWhenNoRecordMatches(): void
    {
        $searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $sortOrderBuilder = $this->createMock(SortOrderBuilder::class);
        $sortOrder = $this->createMock(SortOrder::class);
        $searchResults = $this->createMock(IndexingEntitySearchResultsInterface::class);

        $this->searchCriteriaBuilderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaBuilder);

        $filterCalls = [];
        $searchCriteriaBuilder->method('addFilter')->willReturnCallback(function (...$args) use (&$filterCalls) {
            $filterCalls[] = $args;
            return $this;
        });
        $searchCriteriaBuilder->expects($this->once())->method('setCurrentPage')->with(1);
        $searchCriteriaBuilder->expects($this->once())->method('setPageSize')->with(1);
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
            ->with($searchCriteria)
            ->willReturn($searchResults);

        $searchResults->expects($this->once())->method('getItems')->willReturn([]);

        $this->expectException(NoSuchEntityException::class);

        $this->model->get('category', 123);
    }
}
