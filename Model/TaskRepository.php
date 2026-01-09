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

namespace AthosCommerce\Feed\Model;

use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Api\Data\TaskSearchResultsInterface;
use AthosCommerce\Feed\Api\Data\TaskSearchResultsInterfaceFactory;
use AthosCommerce\Feed\Api\TaskRepositoryInterface;
use AthosCommerce\Feed\Model\ResourceModel\Task as TaskResource;
use AthosCommerce\Feed\Model\ResourceModel\Task\Collection;
use AthosCommerce\Feed\Model\ResourceModel\Task\CollectionFactory;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class TaskRepository implements TaskRepositoryInterface
{
    /**
     * @var TaskFactory
     */
    private $taskFactory;
    /**
     * @var TaskResource
     */
    private $taskResource;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var TaskSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;
    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;
    /**
     * @var JoinProcessorInterface
     */
    private $joinProcessor;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param TaskFactory $taskFactory
     * @param TaskResource $taskResource
     * @param CollectionFactory $collectionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TaskSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $joinProcessor
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        TaskFactory $taskFactory,
        TaskResource $taskResource,
        CollectionFactory $collectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TaskSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $joinProcessor,
        AthosCommerceLogger $logger
    ) {
        $this->taskFactory = $taskFactory;
        $this->taskResource = $taskResource;
        $this->collectionFactory = $collectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->joinProcessor = $joinProcessor;
        $this->logger = $logger;
    }

    /**
     * @param int $id
     *
     * @return TaskInterface
     * @return TaskInterface
     * @throws NoSuchEntityException
     */
    public function get(int $id): TaskInterface
    {
        /** @var Task $task */
        $task = $this->taskFactory->create();
        $this->taskResource->load($task, $id);
        if (!$task->getEntityId()) {
            throw new NoSuchEntityException(__('The Task with the "%1" ID doesn\'t exist.', $id));
        }

        return $task;
    }

    /**
     * @param SearchCriteriaInterface|null $searchCriteria
     *
     * @return TaskSearchResultsInterface
     * @return TaskSearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(?SearchCriteriaInterface $searchCriteria = null): TaskSearchResultsInterface
    {
        if (!$searchCriteria) {
            $searchCriteria = $this->searchCriteriaBuilder->create();
        }

        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $this->joinProcessor->process(
            $collection,
            TaskInterface::class
        );
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var TaskSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        $this->logger->info('TaskRepository collection',
            [
                'method' => __METHOD__,
                'query' => $collection->getSelect()->__toString(),
            ]
        );

        return $searchResults;
    }

    /**
     * @param TaskInterface $task
     *
     * @return TaskInterface
     * @return TaskInterface
     * @throws CouldNotSaveException
     */
    public function save(TaskInterface $task): TaskInterface
    {
        try {
            $this->taskResource->save($task);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()), $exception);
        }

        return $task;
    }

    /**
     * @param TaskInterface $task
     *
     * @return void
     * @throws CouldNotDeleteException
     */
    public function delete(TaskInterface $task): void
    {
        try {
            $this->taskResource->delete($task);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()), $exception);
        }
    }

    /**
     * @param int $id
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $id): void
    {
        $this->delete($this->get($id));
    }
}
