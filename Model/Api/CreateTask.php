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

namespace AthosCommerce\Feed\Model\Api;

use Exception;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use AthosCommerce\Feed\Api\CreateTaskInterface;
use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Api\Data\TaskInterfaceFactory;
use AthosCommerce\Feed\Api\MetadataInterface;
use AthosCommerce\Feed\Api\TaskRepositoryInterface;
use AthosCommerce\Feed\Exception\UniqueTaskException;
use AthosCommerce\Feed\Exception\ValidationException;
use AthosCommerce\Feed\Model\Task\TypeList;
use AthosCommerce\Feed\Model\Task\UniqueCheckerPool;
use AthosCommerce\Feed\Model\Task\ValidatorPool;
use Psr\Log\LoggerInterface;

class CreateTask implements CreateTaskInterface
{
    /**
     * @var TaskRepositoryInterface
     */
    private $taskRepository;
    /**
     * @var TaskInterfaceFactory
     */
    private $taskFactory;
    /**
     * @var ValidatorPool
     */
    private $validatorPool;
    /**
     * @var TypeList
     */
    private $typeList;
    /**
     * @var UniqueCheckerPool
     */
    private $uniqueCheckerPool;

    /**
     * @var Manager
     */
    private $moduleManager;
    /**
     * @var LoggerInterface
     */
    private $logger;

    private $moduleList = [
        'Magento_InventoryReservationsApi',
        'Magento_InventorySalesApi',
        'Magento_InventoryCatalogApi',
        'Magento_Inventory'
    ];

    /**
     * @param TaskRepositoryInterface $taskRepository
     * @param TaskInterfaceFactory $taskFactory
     * @param ValidatorPool $validatorPool
     * @param TypeList $typeList
     * @param UniqueCheckerPool $uniqueCheckerPool
     * @param Manager $moduleManager
     * @param LoggerInterface $logger
     * @param array $moduleList
     */
    public function __construct(
        TaskRepositoryInterface $taskRepository,
        TaskInterfaceFactory    $taskFactory,
        ValidatorPool           $validatorPool,
        TypeList                $typeList,
        UniqueCheckerPool       $uniqueCheckerPool,
        Manager                 $moduleManager,
        LoggerInterface         $logger,
        array                   $moduleList = []
    )
    {
        $this->taskRepository = $taskRepository;
        $this->taskFactory = $taskFactory;
        $this->validatorPool = $validatorPool;
        $this->typeList = $typeList;
        $this->uniqueCheckerPool = $uniqueCheckerPool;
        $this->moduleManager = $moduleManager;
        $this->moduleList = array_merge($this->moduleList, $moduleList);
        $this->logger = $logger;
    }

    /**
     * @param string $type
     * @param array $payload
     * @return TaskInterface
     * @throws CouldNotSaveException
     * @throws ValidationException
     * @throws Exception
     * @throws NoSuchEntityException
     */
    public function execute(string $type, $payload): TaskInterface
    {
        $this->logger->info('Task creation started', [
            'method' => __METHOD__,
            'type' => $type,
            'payload' => $payload,
            'payloadType' => gettype($payload)
        ]);

        if (!is_array($payload)) {
            $this->logger->error('Invalid payload found', [
                'method' => __METHOD__,
                'type' => $type,
                'payload' => $payload,
                'payloadType' => gettype($payload)
            ]);
            throw new Exception((string)__('$payload must be array'));
        }

        if (!empty($payload['isMsiEnabled']) && $this->isMsiEnabled()) {
            $message = 'MSI is enabled via payload and MSI module is enabled. Using MsiStockProvider for stock resolution.';
        } else {
            $message = 'MSI is disabled via payload or MSI modules are not installed. Using LegacyStockProvider for stock resolution.';
        }

        $this->logger->info(
            'MSI Check', [
                'method' => __METHOD__,
                'type' => $type,
                'payload' => $payload,
                'isMsiEnabledViaPayload' => array_key_exists('isMsiEnabled', $payload)
                    ? $payload['isMsiEnabled']
                    : '',
                'isMsiModuleEnabled' => $this->isMsiEnabled(),
                'message' => $message
            ]
        );

        if (!$this->typeList->exist($type)) {
            $availableTaskTypes = implode(', ', $this->typeList->getAll());
            $message = [
                (string)__('Invalid task type \'%1\', available task types: %2', $type, $availableTaskTypes)
            ];
            $this->logger->error('Invalid task type', [
                'method' => __METHOD__,
                'type' => $type,
                'payload' => $payload,
                'typeListType' => gettype($type),
                'availableTypes' => $availableTaskTypes,
                'message' => $message
            ]);
            throw new ValidationException($message);
        }

        $validator = $this->validatorPool->get($type);
        if ($validator) {
            $validationResult = $validator->validate($payload);
            if (!$validationResult->isValid()) {
                $errors = $validationResult->getErrors();
                $this->logger->error('Task payload validation failed', [
                    'method' => __METHOD__,
                    'type' => $type,
                    'payload' => $payload,
                    'validatorType' => gettype($validator),
                    'error' => $errors,
                    'message' => 'Please check the data is sent in correct format.'
                ]);
                throw new ValidationException($errors);
            }
        }

        $uniqueChecker = $this->uniqueCheckerPool->get($type);
        if ($uniqueChecker && !$uniqueChecker->check($payload)) {
            $this->logger->warning('Duplicate task detected', [
                'method' => __METHOD__,
                'type' => $type,
                'payload' => $payload,
                'uniqueCheckerType' => gettype($uniqueChecker),
                'message' => 'please re-run update index'
            ]);
            throw new UniqueTaskException();
        }

        /** @var TaskInterface $task */
        $task = $this->taskFactory->create();
        $task->setType($type)
            ->setPayload($payload)
            ->setStatus(MetadataInterface::TASK_STATUS_PENDING);
        $this->logger->info('Attempting to save task', [
            'method' => __METHOD__,
            'type' => $type,
            'payload' => $payload,
            'payloadType' => gettype($payload)
        ]);

        $savedTask = $this->taskRepository->save($task);

        $this->logger->info('Task saved and created successfully', [
            'method' => __METHOD__,
            'type' => $type,
            'payload' => $payload,
            'payloadType' => gettype($payload),
            'taskId' => $savedTask->getId(),
            'status' => $savedTask->getStatus()
        ]);

        return $savedTask;
    }

    /**
     * @return bool
     */
    private function isMsiEnabled(): bool
    {
        $moduleExists = true;
        foreach ($this->moduleList as $moduleName) {
            if (!$this->moduleManager->isEnabled($moduleName)) {
                $moduleExists = false;
                break;
            }
        }

        if (!$moduleExists) {
            return false;
        }

        return true;
    }
}
