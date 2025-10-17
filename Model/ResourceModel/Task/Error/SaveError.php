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
use AthosCommerce\Feed\Model\ResourceModel\Task;

class SaveError
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var DeleteErrors
     */
    private $deleteErrors;

    /**
     * SaveError constructor.
     * @param ResourceConnection $resourceConnection
     * @param DeleteErrors $deleteErrors
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        DeleteErrors $deleteErrors
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->deleteErrors = $deleteErrors;
    }

    /**
     * @param int $taskId
     * @param TaskErrorInterface $error
     * @throws \Exception
     */
    public function execute(int $taskId, TaskErrorInterface $error) : void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(Task::ERROR_TABLE);
        if (!$error->getCode() || !$error->getMessage()) {
            throw new \Exception((string) __('error or code cannot be null'));
        }

        $connection->beginTransaction();
        try {
            $this->deleteErrors->execute([$taskId]);
            $data = [
                'task_id' => $taskId,
                'code' => $error->getCode(),
                'message' => $error->getMessage()
            ];
            $connection->insert($tableName, $data);
            $connection->commit();
        } catch (\Exception $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }
}
