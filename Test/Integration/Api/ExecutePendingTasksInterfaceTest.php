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

namespace AthosCommerce\Feed\Test\Integration\Api;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Api\ExecutePendingTasksInterface;
use AthosCommerce\Feed\Api\MetadataInterface;
use AthosCommerce\Feed\Api\TaskRepositoryInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExecutePendingTasksInterfaceTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var ExecutePendingTasksInterface
     */
    private $executePendingTasks;
    /**
     * @var TaskRepositoryInterface
     */
    private $taskRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->executePendingTasks = $this->objectManager->get(ExecutePendingTasksInterface::class);
        $this->taskRepository = $this->objectManager->get(TaskRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configure_generate_feed_mock.php
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testExecute() : void
    {
        $task = $this->createPendingTask();
        $result = $this->executePendingTasks->execute();
        $this->assertCount(1, $result);
        $task = $this->taskRepository->get($task->getEntityId());
        $this->assertEquals(MetadataInterface::TASK_STATUS_SUCCESS, $task->getStatus());
        $this->taskRepository->delete($task);
    }
    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configure_generate_feed_mock.php
     *
     * @return void
     * @throws LocalizedException
     */
    public function testExecuteWithNoTaskInDb() : void
    {
        $result = $this->executePendingTasks->execute();
        $this->assertEmpty($result);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $tasks = $this->taskRepository->getList($searchCriteria)->getItems();
        $this->assertEmpty($tasks);
    }
    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configure_generate_feed_mock.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/processing_task.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/success_task.php
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testExecuteWithNoPendingTaskInDb() : void
    {
        $result = $this->executePendingTasks->execute();
        $this->assertEmpty($result);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $tasks = $this->taskRepository->getList($searchCriteria)->getItems();
        $this->assertNotEmpty($tasks);
    }

    /**
     * @return TaskInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    private function createPendingTask() : TaskInterface
    {
        /** @var TaskInterface $task */
        $task = $this->objectManager->create(TaskInterface::class);
        $task->setPayload($this->getPayload())
            ->setType(MetadataInterface::FEED_GENERATION_TASK_CODE)
            ->setStatus(MetadataInterface::TASK_STATUS_PENDING);

        return $this->taskRepository->save($task);
    }

    /**
     * @return array
     */
    private function getPayload() : array
    {
        return [
            'preSignedUrl' => 'https://testurl.com'
        ];
    }
}
