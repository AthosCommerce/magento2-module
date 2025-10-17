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

use Exception;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;
use AthosCommerce\Feed\Api\Data\TaskErrorInterface;
use AthosCommerce\Feed\Api\Data\TaskErrorInterfaceFactory;
use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Api\ExecuteTaskInterface;
use AthosCommerce\Feed\Api\MetadataInterface;
use AthosCommerce\Feed\Api\TaskRepositoryInterface;
use AthosCommerce\Feed\Exception\GenericException;
use AthosCommerce\Feed\Model\Task\ExecutorPool;

class ExecuteTask implements ExecuteTaskInterface
{
    /**
     * @var ExecutorPool
     */
    private $executorPool;
    /**
     * @var TaskRepositoryInterface
     */
    private $taskRepository;
    /**
     * @var DateTime
     */
    private $dateTime;
    /**
     * @var TaskErrorInterfaceFactory
     */
    private $taskErrorFactory;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ExecuteTask constructor.
     * @param ExecutorPool $executorPool
     * @param TaskRepositoryInterface $taskRepository
     * @param DateTime $dateTime
     * @param TaskErrorInterfaceFactory $taskErrorFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ExecutorPool $executorPool,
        TaskRepositoryInterface $taskRepository,
        DateTime $dateTime,
        TaskErrorInterfaceFactory $taskErrorFactory,
        LoggerInterface $logger
    ) {
        $this->executorPool = $executorPool;
        $this->taskRepository = $taskRepository;
        $this->dateTime = $dateTime;
        $this->taskErrorFactory = $taskErrorFactory;
        $this->logger = $logger;
    }

    /**
     * @param TaskInterface $task
     * @return mixed
     * @throws CouldNotSaveException
     */
    public function execute(TaskInterface $task)
    {
        $executor = $this->executorPool->get($task->getType());
        $time = $this->dateTime->gmtDate();
        $task->setStartedAt($time)
            ->setStatus(MetadataInterface::TASK_STATUS_PROCESSING);
        $this->taskRepository->save($task);
        $result = null;
        try {
            $result = $executor->execute($task);
            $task->setStatus(MetadataInterface::TASK_STATUS_SUCCESS);
        } catch (\Throwable $exception) {
            /** @var TaskErrorInterface $error */
            $error = $this->taskErrorFactory->create();
            $code = $exception instanceof GenericException ? $exception->getCode() : GenericException::CODE;
            $this->logger->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            $error->setMessage($exception->getMessage())
                ->setCode($code);
            $task->setStatus(MetadataInterface::TASK_STATUS_ERROR)
                ->setError($error);
        }
        $time = $this->dateTime->gmtDate();
        $task->setEndedAt($time);
        $this->taskRepository->save($task);
        return $result;
    }
}
