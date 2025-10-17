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

namespace AthosCommerce\Feed\Model\ResourceModel\Task\Error;

use Magento\Framework\App\ResourceConnection;
use AthosCommerce\Feed\Api\Data\TaskErrorInterface;
use AthosCommerce\Feed\Api\Data\TaskErrorInterfaceFactory;
use AthosCommerce\Feed\Model\ResourceModel\Task;

class LoadErrors
{
    /**
     * @var TaskErrorInterfaceFactory
     */
    private $taskErrorFactory;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * LoadErrors constructor.
     * @param TaskErrorInterfaceFactory $taskErrorFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        TaskErrorInterfaceFactory $taskErrorFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->taskErrorFactory = $taskErrorFactory;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param array $taskIds
     * @return array
     * @throws \Exception
     */
    public function execute(array $taskIds) : array
    {
        if (empty($taskIds)) {
            throw new \Exception((string) __('$taskIds cannot be empty'));
        }
        $taskIds = array_map('intval', $taskIds);
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(Task::ERROR_TABLE);
        $select = $connection->select()->from($tableName)
            ->where('task_id in (?)', $taskIds);
        $errors = $connection->fetchAll($select);
        $result = [];
        foreach ($errors as $error) {
            $errorData = [
                TaskErrorInterface::CODE => (int) $error[TaskErrorInterface::CODE],
                TaskErrorInterface::MESSAGE => $error[TaskErrorInterface::MESSAGE]
            ];
            $result[(int) $error['task_id']] = $this->taskErrorFactory->create(['data' => $errorData]);
        }

        return $result;
    }
}
