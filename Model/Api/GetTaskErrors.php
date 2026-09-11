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

namespace AthosCommerce\Feed\Model\Api;

use AthosCommerce\Feed\Api\Data\TaskErrorItemInterface;
use AthosCommerce\Feed\Api\Data\TaskErrorItemInterfaceFactory;
use AthosCommerce\Feed\Api\Data\TaskErrorListResponseInterface;
use AthosCommerce\Feed\Api\Data\TaskErrorListResponseInterfaceFactory;
use AthosCommerce\Feed\Api\GetTaskErrorsInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\ResourceModel\Task;
use Magento\Framework\App\ResourceConnection;

class GetTaskErrors implements GetTaskErrorsInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var TaskErrorItemInterfaceFactory
     */
    private $itemFactory;

    /**
     * @var TaskErrorListResponseInterfaceFactory
     */
    private $responseFactory;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param TaskErrorItemInterfaceFactory $itemFactory
     * @param TaskErrorListResponseInterfaceFactory $responseFactory
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        TaskErrorItemInterfaceFactory $itemFactory,
        TaskErrorListResponseInterfaceFactory $responseFactory,
        AthosCommerceLogger $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->itemFactory = $itemFactory;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
    }

    /**
     * @param int $currentPage
     * @param int $pageSize
     * @param int|null $taskId
     * @return TaskErrorListResponseInterface
     */
    public function getList(
        int $currentPage = 1,
        int $pageSize = 20,
        ?int $taskId = null
    ): TaskErrorListResponseInterface {
        $currentPage = max(1, $currentPage);
        $pageSize = max(1, $pageSize);

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(Task::ERROR_TABLE);

        $countSelect = $connection->select()->from($tableName, ['COUNT(*)']);
        $dataSelect = $connection->select()
            ->from($tableName, ['task_id', 'code', 'message', 'created_at'])
            ->order('created_at DESC')
            ->order('task_id DESC')
            ->limitPage($currentPage, $pageSize);

        if ($taskId !== null) {
            $countSelect->where('task_id = ?', $taskId);
            $dataSelect->where('task_id = ?', $taskId);
        }

        $total = (int)$connection->fetchOne($countSelect);
        $rows = $connection->fetchAll($dataSelect);

        $items = [];
        foreach ($rows as $row) {
            /** @var TaskErrorItemInterface $item */
            $item = $this->itemFactory->create();
            $item->setTaskId((int)$row['task_id']);
            $item->setCode((int)$row['code']);
            $item->setMessage((string)$row['message']);
            $item->setCreatedAt($row['created_at'] ?? null);
            $items[] = $item;
        }

        /** @var TaskErrorListResponseInterface $response */
        $response = $this->responseFactory->create();
        $response->setItems($items);
        $response->setTotal($total);
        $response->setCurrentPage($currentPage);
        $response->setPageSize($pageSize);

        $this->logger->info('[TaskErrorsApi] Fetched task errors', [
            'method' => __METHOD__,
            'taskId' => $taskId,
            'currentPage' => $currentPage,
            'pageSize' => $pageSize,
            'total' => $total,
        ]);

        return $response;
    }
}
