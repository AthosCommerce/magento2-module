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

namespace AthosCommerce\Feed\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Api\Data\TaskSearchResultsInterface;

interface TaskRepositoryInterface
{
    /**
     * @param int $id
     * @return AthosCommerce\Feed\Api\Data\TaskInterface
     * @throws NoSuchEntityException
     */
    public function get(int $id) : TaskInterface;

    /**
     * @param SearchCriteriaInterface|null $searchCriteria
     * @return AthosCommerce\Feed\Api\Data\TaskSearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(?SearchCriteriaInterface $searchCriteria = null) : TaskSearchResultsInterface;

    /**
     * @param TaskInterface $task
     * @return AthosCommerce\Feed\Api\Data\TaskInterface
     * @throws CouldNotSaveException
     */
    public function save(TaskInterface $task) : TaskInterface;

    /**
     * @param TaskInterface $task
     * @return void
     * @throws CouldNotDeleteException
     */
    public function delete(TaskInterface $task) : void;

    /**
     * @param int $id
     * @return void
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $id) : void;
}
