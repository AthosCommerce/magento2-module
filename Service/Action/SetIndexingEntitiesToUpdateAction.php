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

namespace AthosCommerce\Feed\Service\Action;

use AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface;
use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Model\Source\Actions;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Psr\Log\LoggerInterface;

class SetIndexingEntitiesToUpdateAction implements SetIndexingEntitiesToUpdateActionInterface
{
    /**
     * @var IndexingEntityRepositoryInterface
     */
    private $indexingEntityRepository;
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param IndexingEntityRepositoryInterface $indexingEntityRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        IndexingEntityRepositoryInterface $indexingEntityRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        LoggerInterface $logger
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->indexingEntityRepository = $indexingEntityRepository;
        $this->logger = $logger;
    }

    /**
     * @param array $entityIds
     *
     * @return void
     */
    public function execute(array $entityIds): void
    {
        $indexingEntities = $this->getIndexingEntities(iterator_to_array($entityIds));
        try {
            $indexingEntityIds = [];
            foreach ($indexingEntities as $indexingEntity) {
                if (!$indexingEntity->getIsIndexable()) {
                    continue;
                }
                $indexingEntityIds[] = $indexingEntity->getId();
                $indexingEntity->setNextAction(Actions::UPSERT);
                $this->indexingEntityRepository->save($indexingEntity);
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                'Method: {method} - Error: {exception}',
                [
                    'method' => __METHOD__,
                    'exception' => $exception->getMessage(),
                    'indexingEntityIds' => $indexingEntityIds,
                ],
            );
        }
        foreach ($indexingEntities as $indexingEntity) {
            if (method_exists($indexingEntity, 'clearInstance')) {
                $indexingEntity->clearInstance();
            }
        }
        unset($indexingEntities);
    }

    /**
     * @param array $entityIds
     *
     * @return array
     */
    private function getIndexingEntities(array $entityIds): array
    {
        $indexingEntities = [];
        $entityIds = array_filter($entityIds);
        if (!empty($entityIds)) {
            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $searchCriteriaBuilder->addFilter(
                IndexingEntity::ENTITY_ID,
                $entityIds,
                'in',
            );
            $searchCriteria = $searchCriteriaBuilder->create();
            $searchResult = $this->indexingEntityRepository->getList($searchCriteria);
            $indexingEntities = $searchResult->getItems();
        }

        return $indexingEntities;
    }
}
