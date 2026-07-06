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

namespace AthosCommerce\Feed\Test\Integration\Model;

use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Api\Data\TaskInterfaceFactory;
use AthosCommerce\Feed\Api\MetadataInterface;
use AthosCommerce\Feed\Api\TaskRepositoryInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class TaskTest extends TestCase
{
    /**
     * @var TaskInterfaceFactory
     */
    private $taskFactory;

    /**
     * @var TaskRepositoryInterface
     */
    private $taskRepository;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->taskFactory = $objectManager->get(TaskInterfaceFactory::class);
        $this->taskRepository = $objectManager->get(TaskRepositoryInterface::class);
        $this->serializer = $objectManager->get(SerializerInterface::class);

        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function testSaveAndLoadTask(): void
    {
        $task = $this->taskFactory->create();
        $task->setType(MetadataInterface::FEED_GENERATION_TASK_CODE);
        $task->setStatus(MetadataInterface::TASK_STATUS_PENDING);
        $task->setPayload([
            'stores' => 'default',
            'format' => 'json',
        ]);
        $task->setStartedAt('2026-07-04 10:00:00');
        $task->setEndedAt('2026-07-06 10:30:00');
        $task->setProductCount(15);
        $task->setFileSize(1024);

        $savedTask = $this->taskRepository->save($task);

        $this->assertGreaterThan(0, $savedTask->getEntityId());
        $this->assertNotEmpty($savedTask->getCreatedAt());

        $loadedTask = $this->taskRepository->get((int)$savedTask->getEntityId());

        $this->assertSame((int)$savedTask->getEntityId(), $loadedTask->getEntityId());
        $this->assertSame(MetadataInterface::FEED_GENERATION_TASK_CODE, $loadedTask->getType());
        $this->assertSame(MetadataInterface::TASK_STATUS_PENDING, $loadedTask->getStatus());
        $this->assertSame('2026-07-04 10:00:00', $loadedTask->getStartedAt());
        $this->assertSame('2026-07-06 10:30:00', $loadedTask->getEndedAt());
        $this->assertSame(15, $loadedTask->getProductCount());
        $this->assertSame(1024, $loadedTask->getFileSize());

        $payload = $loadedTask->getPayload();
        $this->assertIsArray($payload);
        $this->assertSame('default', $payload['stores']);
        $this->assertSame('json', $payload['format']);

        $this->taskRepository->delete($loadedTask);
    }

    /**
     * @magentoAppIsolation enabled
     *
     * @throws CouldNotSaveException
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function testBeforeSaveSetsCreatedAtAutomatically(): void
    {
        $task = $this->taskFactory->create();
        $task->setType(MetadataInterface::FEED_GENERATION_TASK_CODE);
        $task->setStatus(MetadataInterface::TASK_STATUS_PENDING);
        $task->setPayload([
            'stores' => 'default',
            'format' => 'json',
        ]);

        $this->assertNull($task->getCreatedAt());

        $savedTask = $this->taskRepository->save($task);

        $this->assertNotEmpty($savedTask->getCreatedAt());

        $loadedTask = $this->taskRepository->get((int)$savedTask->getEntityId());
        $this->assertNotEmpty($loadedTask->getCreatedAt());

        $this->taskRepository->delete($loadedTask);
    }

    /**
     * @magentoAppIsolation enabled
     *
     * @throws CouldNotSaveException
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function testFileSizeDefaultsToZero(): void
    {
        $task = $this->taskFactory->create();
        $task->setType(MetadataInterface::FEED_GENERATION_TASK_CODE);
        $task->setStatus(MetadataInterface::TASK_STATUS_PENDING);
        $task->setPayload([
            'stores' => 'default',
            'format' => 'json',
        ]);

        $savedTask = $this->taskRepository->save($task);
        $loadedTask = $this->taskRepository->get((int)$savedTask->getEntityId());

        $this->assertSame(0, $loadedTask->getFileSize());

        $this->taskRepository->delete($loadedTask);
    }

    /**
     * @magentoAppIsolation enabled
     *
     * Covers Task::getPayload() branch where payload is a serialized string.
     *
     * @throws CouldNotSaveException
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function testGetPayloadUnserializesSerializedString(): void
    {
        $task = $this->taskFactory->create();
        $task->setType(MetadataInterface::FEED_GENERATION_TASK_CODE);
        $task->setStatus(MetadataInterface::TASK_STATUS_PENDING);
        $task->setPayload([
            'stores' => 'default',
            'format' => 'json',
        ]);

        $savedTask = $this->taskRepository->save($task);
        $loadedTask = $this->taskRepository->get((int)$savedTask->getEntityId());

        $serializedPayload = $this->serializer->serialize([
            'stores' => 'default',
            'format' => 'json',
            'customerId' => 123,
        ]);

        $loadedTask->setData(TaskInterface::PAYLOAD, $serializedPayload);

        $payload = $loadedTask->getPayload();

        $this->assertIsArray($payload);
        $this->assertSame('default', $payload['stores']);
        $this->assertSame('json', $payload['format']);
        $this->assertSame(123, $payload['customerId']);
        $this->assertSame($payload, $loadedTask->getData(TaskInterface::PAYLOAD));

        $this->taskRepository->delete($loadedTask);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/task_with_error.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/task_with_error_rollback.php
     *
     * @throws NoSuchEntityException
     */
    public function testAfterLoadPopulatesError(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var \Magento\Framework\App\ResourceConnection $resourceConnection */
        $resourceConnection = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);

        $task = $this->taskFactory->create();
        $task->setType(MetadataInterface::FEED_GENERATION_TASK_CODE);
        $task->setStatus(MetadataInterface::TASK_STATUS_ERROR);
        $task->setPayload([
            'preSignedUrl' => 'https://testurl.com',
        ]);

        $savedTask = $this->taskRepository->save($task);
        $taskId = (int)$savedTask->getEntityId();

        $connection = $resourceConnection->getConnection();
        $tableName = $resourceConnection->getTableName(\AthosCommerce\Feed\Model\ResourceModel\Task::ERROR_TABLE);

        $connection->insert($tableName, [
            'task_id' => $taskId,
            'code' => 1000,
            'message' => 'Integration test task error message',
        ]);

        $loadedTask = $this->taskRepository->get($taskId);

        $this->assertNotNull($loadedTask->getError());
        $this->assertSame(1000, (int)$loadedTask->getError()->getCode());
        $this->assertSame('Integration test task error message', $loadedTask->getError()->getMessage());

        $this->taskRepository->delete($loadedTask);
    }
}
