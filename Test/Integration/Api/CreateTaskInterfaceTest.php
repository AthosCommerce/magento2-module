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

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Api\CreateTaskInterface;
use AthosCommerce\Feed\Api\MetadataInterface;
use AthosCommerce\Feed\Api\TaskRepositoryInterface;
use AthosCommerce\Feed\Exception\UniqueTaskException;
use AthosCommerce\Feed\Exception\ValidationException;
use AthosCommerce\Feed\Model\Task\TypeList;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreateTaskInterfaceTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var CreateTaskInterface
     */
    private $createTask;
    /**
     * @var TaskRepositoryInterface
     */
    private $taskRepository;
    /**
     * @var TypeList
     */
    private $typeList;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->createTask = $this->objectManager->get(CreateTaskInterface::class);
        $this->taskRepository = $this->objectManager->get(TaskRepositoryInterface::class);
        $this->typeList = $this->objectManager->get(TypeList::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     *
     * @return void
     * @throws CouldNotSaveException
     * @throws UniqueTaskException
     * @throws ValidationException
     */
    public function testCreateTask(): void
    {
        $payload = $this->getPayload();
        $payload['format'] = 'json';
        $type = MetadataInterface::FEED_GENERATION_TASK_CODE;
        $result = $this->createTask->execute($type, $payload);
        $this->assertEquals(MetadataInterface::TASK_STATUS_PENDING, $result->getStatus());
        $this->assertEquals(MetadataInterface::FEED_GENERATION_TASK_CODE, $result->getType());
        $this->assertGreaterThan(0, $result->getEntityId());
    }

    /**
     * @magentoAppIsolation enabled
     *
     * @return void
     * @throws UniqueTaskException
     * @throws ValidationException
     * @throws CouldNotSaveException
     */
    public function testCreateTaskWithInvalidType(): void
    {
        $payload = $this->getPayload();
        $type = '___test_invalid___';
        $allTypes = implode(', ', $this->typeList->getAll());
        $message = [
            (string)__('Invalid task type \'%1\', available task types: %2', $type, $allTypes),
        ];
        $this->expectExceptionObject(new ValidationException($message));
        $this->createTask->execute($type, $payload);
    }

    /**
     * @magentoAppIsolation enabled
     *
     * @return void
     * @throws CouldNotSaveException
     * @throws UniqueTaskException
     * @throws ValidationException
     */
    public function testCreateTaskWithInvalidPayload(): void
    {
        $payload = $this->getPayload();
        $payload['format'] = '___invalid_format___';
        $type = MetadataInterface::FEED_GENERATION_TASK_CODE;
        $this->expectException(ValidationException::class);
        $this->createTask->execute($type, $payload);
    }

    /**
     * @magentoAppIsolation enabled
     *
     * @return void
     * @throws CouldNotSaveException
     * @throws UniqueTaskException
     * @throws ValidationException
     */
    public function testCreateNotUniqueTask(): void
    {
        $payload = $this->getPayload();
        $type = MetadataInterface::FEED_GENERATION_TASK_CODE;
        $this->createTask->execute($type, $payload);
        $this->expectException(UniqueTaskException::class);
        $this->createTask->execute($type, $payload);
    }

    /**
     * @return array
     */
    private function getPayload(): array
    {
        return [
            'preSignedUrl' => 'https://testurl.com',
        ];
    }
}
