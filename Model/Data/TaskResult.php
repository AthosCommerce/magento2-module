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

namespace AthosCommerce\Feed\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Api\Data\TaskResultInterface;

class TaskResult extends AbstractSimpleObject implements TaskResultInterface
{

    /**
     * @return TaskInterface|null
     */
    public function getTask(): ?TaskInterface
    {
        return $this->_get(self::TASK);
    }

    /**
     * @param TaskInterface $task
     * @return TaskResultInterface
     */
    public function setTask(TaskInterface $task): TaskResultInterface
    {
        return $this->setData(self::TASK, $task);
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->_get(self::RESULT);
    }

    /**
     * @param $result
     * @return mixed
     */
    public function setResult($result)
    {
        return $this->setData(self::RESULT, $result);
    }
}
