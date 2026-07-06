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

use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Api\MetadataInterface;
use AthosCommerce\Feed\Api\TaskRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var TaskRepositoryInterface $taskRepository */
$taskRepository = $objectManager->get(TaskRepositoryInterface::class);

/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);

$searchCriteria = $searchCriteriaBuilder
    ->addFilter(TaskInterface::TYPE, MetadataInterface::FEED_GENERATION_TASK_CODE)
    ->addFilter(TaskInterface::STATUS, MetadataInterface::TASK_STATUS_ERROR)
    ->create();

foreach ($taskRepository->getList($searchCriteria)->getItems() as $task) {
    $payload = $task->getPayload();
    if (($payload['preSignedUrl'] ?? null) === 'https://testurl.com') {
        $taskRepository->delete($task);
    }
}
