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

namespace AthosCommerce\Feed\Model\Task\GenerateFeed;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Api\MetadataInterface;
use AthosCommerce\Feed\Api\TaskRepositoryInterface;
use AthosCommerce\Feed\Model\Task\UniqueCheckerInterface;

class UniqueChecker implements UniqueCheckerInterface
{
    /**
     * @var TaskRepositoryInterface
     */
    private $taskRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * UniqueChecker constructor.
     * @param TaskRepositoryInterface $taskRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        TaskRepositoryInterface $taskRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->taskRepository = $taskRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param array $payload
     * @return bool
     * @throws LocalizedException
     */
    public function check(array $payload): bool
    {
        $tasks = $this->getSuitableTasks();
        if (empty($tasks)) {
            return true;
        }

        $result = true;
        foreach ($tasks as $task) {
            $taskPayload = $task->getPayload();
            if ($this->comparePayloads($taskPayload, $payload)) {
                $result = false;
                break;
            }
        }

        return $result;
    }

    /**
     * @param array $firstPayload
     * @param array $secondPayload
     * @return bool
     */
    private function comparePayloads(array $firstPayload, array $secondPayload) : bool
    {
        $diff = array_diff_key($firstPayload, $secondPayload);
        if (!empty($diff)) {
            return false;
        }

        $diff = array_diff_key($secondPayload, $firstPayload);
        if (!empty($diff)) {
            return false;
        }

        $result = true;
        foreach ($firstPayload as $key => $value) {
            $checkValue = $secondPayload[$key];
            if ((!is_array($value) && $value != $checkValue)
                || (is_array($value)
                    && (array_diff($checkValue, $value) || array_diff($value, $checkValue))
                )
            ) {
                $result = false;
                break;
            }
        }

        return $result;
    }

    /**
     * @return TaskInterface[]
     * @throws LocalizedException
     */
    private function getSuitableTasks() : array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(TaskInterface::TYPE, MetadataInterface::FEED_GENERATION_TASK_CODE)
            ->addFilter(
                TaskInterface::STATUS,
                [MetadataInterface::TASK_STATUS_PENDING, MetadataInterface::TASK_STATUS_PROCESSING],
                'in'
            )
            ->create();
        return $this->taskRepository->getList($searchCriteria)->getItems();
    }
}
