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

use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Api\Data\TaskSearchResultsInterfaceFactory;
use AthosCommerce\Feed\Api\Data\TaskSearchResultsInterface;
use AthosCommerce\Feed\Model\ResourceModel\Task as TaskResource;
use AthosCommerce\Feed\Model\ResourceModel\Task\CollectionFactory;
use AthosCommerce\Feed\Model\ResourceModel\Task\Collection;
use AthosCommerce\Feed\Model\Task;
use AthosCommerce\Feed\Model\TaskFactory;
use AthosCommerce\Feed\Model\TaskRepository;

class TaskRepositoryTest extends TestCase
{
    /**
     * @var MockObject|TaskFactory|TaskFactory&MockObject
     */
    private $taskFactoryMock;

    /**
     * @var MockObject|TaskResource|TaskResource&MockObject
     */
    private $taskResourceMock;

    /**
     * @var MockObject|CollectionFactory|CollectionFactory&MockObject
     */
    private $collectionFactoryMock;

    /**
     * @var SearchCriteriaBuilder|SearchCriteriaBuilder&MockObject|MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var MockObject|TaskSearchResultsInterfaceFactory|TaskSearchResultsInterfaceFactory&MockObject
     */
    private $searchResultsFactoryMock;

    /**
     * @var CollectionProcessorInterface&MockObject|MockObject
     */
    private $collectionProcessorMock;

    /**
     * @var JoinProcessorInterface&MockObject|MockObject
     */
    private $joinProcessorMock;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->taskFactoryMock = $this->createMock(TaskFactory::class);
        $this->taskResourceMock = $this->createMock(TaskResource::class);
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchResultsFactoryMock = $this->createMock(TaskSearchResultsInterfaceFactory::class);
        $this->collectionProcessorMock = $this->createMock(CollectionProcessorInterface::class);
        $this->joinProcessorMock = $this->createMock(JoinProcessorInterface::class);
        $this->taskRepository = new TaskRepository(
            $this->taskFactoryMock,
            $this->taskResourceMock,
            $this->collectionFactoryMock,
            $this->searchCriteriaBuilderMock,
            $this->searchResultsFactoryMock,
            $this->collectionProcessorMock,
            $this->joinProcessorMock
        );
    }

    /**
     * @return void
     * @throws CouldNotSaveException
     */
    public function testSave()
    {
        $taskMock = $this->getMockBuilder(Task::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->taskResourceMock->expects($this->once())
            ->method('save')
            ->willReturnSelf();

        $this->assertSame($taskMock, $this->taskRepository->save($taskMock));
    }

    public function testSaveExceptionCase()
    {
        $taskMock = $this->getMockBuilder(Task::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->taskResourceMock->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception());

        $this->expectException(CouldNotSaveException::class);
        $this->taskRepository->save($taskMock);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testDeleteById()
    {
        $taskId = 123;

        $taskMock = $this->getMockBuilder(Task::class)
            ->disableOriginalConstructor()
            ->getMock();
        $taskMock->expects($this->any())
            ->method('getEntityId')
            ->willReturn($taskId);
        $this->taskFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($taskMock);
        $this->taskResourceMock->expects($this->once())
            ->method('load')
            ->with($taskMock, $taskId)
            ->willReturnSelf();
        $this->taskResourceMock->expects($this->once())
            ->method('delete')
            ->with($taskMock);

        $this->assertNull($this->taskRepository->deleteById($taskId));
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testDeleteByIdExceptionCase()
    {
        $taskId = 123;

        $taskMock = $this->getMockBuilder(Task::class)
            ->disableOriginalConstructor()
            ->getMock();
        $taskMock->expects($this->any())
            ->method('getEntityId')
            ->willReturn($taskId);
        $this->taskFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($taskMock);
        $this->taskResourceMock->expects($this->once())
            ->method('load')
            ->with($taskMock, $taskId)
            ->willReturnSelf();
        $this->taskResourceMock->expects($this->once())
            ->method('delete')
            ->willThrowException(new \Exception());

        $this->expectException(CouldNotDeleteException::class);
        $this->taskRepository->deleteById($taskId);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGet()
    {
        $taskId = 1;
        $taskMock = $this->getMockBuilder(Task::class)->disableOriginalConstructor()->getMock();
        $this->taskFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($taskMock);
        $this->taskResourceMock->expects($this->once())
            ->method('load')
            ->with($taskMock, $taskId)
            ->willReturnSelf();
        $taskMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn($taskId);

        $this->assertSame($taskMock, $this->taskRepository->get($taskId));
    }
    /**
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetExceptionCase()
    {
        $taskId = 1;
        $taskMock = $this->getMockBuilder(Task::class)->disableOriginalConstructor()->getMock();
        $this->taskFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($taskMock);
        $this->taskResourceMock->expects($this->once())
            ->method('load')
            ->with($taskMock, $taskId)
            ->willReturnSelf();
        $taskMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(null);

        $this->expectException(NoSuchEntityException::class);
        $this->taskRepository->get($taskId);
    }

    public function testGetList()
    {
        $collectionSize = 1;
        $collectionItems = [
            $this->getMockBuilder(Task::class)->disableOriginalConstructor()->getMock()
        ];

        $taskCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($taskCollectionMock);
        $searchCriteriaMock = $this->getMockForAbstractClass(SearchCriteriaInterface::class);
        $searchResultMock = $this->getMockForAbstractClass(TaskSearchResultsInterface::class);

        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($taskCollectionMock);
        $this->joinProcessorMock->expects($this->once())
            ->method('process')
            ->with($taskCollectionMock, TaskInterface::class);
        $this->collectionProcessorMock->expects($this->once())
            ->method('process')
            ->with($searchCriteriaMock, $taskCollectionMock);
        $this->searchResultsFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($searchResultMock);
        $searchResultMock->expects($this->once())
            ->method('setSearchCriteria')
            ->with($searchCriteriaMock);

        $taskCollectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn($collectionItems);
        $searchResultMock->expects($this->once())
            ->method('setItems')
            ->with($collectionItems)
            ->willReturnSelf();

        $taskCollectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn($collectionSize);
        $searchResultMock->expects($this->once())
            ->method('setTotalCount')
            ->with($collectionSize);

        $this->assertSame($searchResultMock, $this->taskRepository->getList($searchCriteriaMock));
    }
}
