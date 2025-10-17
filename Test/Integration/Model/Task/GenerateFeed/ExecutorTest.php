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

namespace AthosCommerce\Feed\Test\Integration\Model\Task\GenerateFeed;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Api\MetadataInterface;
use AthosCommerce\Feed\Exception\GenericException;
use AthosCommerce\Feed\Model\Task\GenerateFeed\Executor;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExecutorTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var Executor
     */
    private $executor;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->executor = $this->objectManager->get(Executor::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configure_aws_client_mock.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products.php
     *
     * @return void
     * @throws CouldNotSaveException
     * @throws GenericException
     */
    public function testExecute() : void
    {
        $task = $this->createPendingTask();
        $this->executor->execute($task);
        // check that we achieve this place and dont have any exceptions
        $this->assertEquals(1,1);
    }

    /**
     * @return TaskInterface
     * @throws CouldNotSaveException
     */
    private function createPendingTask() : TaskInterface
    {
        /** @var TaskInterface $task */
        $task = $this->objectManager->create(TaskInterface::class);
        $task->setPayload($this->getPayload())
            ->setType(MetadataInterface::FEED_GENERATION_TASK_CODE)
            ->setStatus(MetadataInterface::TASK_STATUS_PENDING);

        return $task;
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
