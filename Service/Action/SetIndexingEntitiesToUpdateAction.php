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
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\FilterBuilderFactory;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilderFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

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
     * @var FilterBuilderFactory
     */
    private $filterBuilderFactory;
    /**
     * @var FilterGroupBuilderFactory
     */
    private $filterGroupBuilderFactory;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param IndexingEntityRepositoryInterface $indexingEntityRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param FilterBuilderFactory $filterBuilderFactory
     * @param FilterGroupBuilderFactory $filterGroupBuilderFactory
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        IndexingEntityRepositoryInterface $indexingEntityRepository,
        SearchCriteriaBuilderFactory      $searchCriteriaBuilderFactory,
        FilterBuilderFactory              $filterBuilderFactory,
        FilterGroupBuilderFactory         $filterGroupBuilderFactory,
        AthosCommerceLogger                   $logger
    )
    {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->indexingEntityRepository = $indexingEntityRepository;
        $this->filterBuilderFactory = $filterBuilderFactory;
        $this->filterGroupBuilderFactory = $filterGroupBuilderFactory;
        $this->logger = $logger;
    }

    /**
     * @param array $entityIds
     * @param bool $forceIndexable
     * @return void
     */
    public function execute(array $entityIds, bool $forceIndexable = false): void
    {
        $indexingEntities = $this->getIndexingEntities(iterator_to_array($entityIds));
        try {
            $indexingEntityIds = [];
            foreach ($indexingEntities as $indexingEntity) {
                if (!$indexingEntity->getIsIndexable() && !$forceIndexable) {
                    continue;
                }
                $indexingEntityIds[] = $indexingEntity->getId();
                $indexingEntity->setNextAction(Actions::UPSERT);
                if ($forceIndexable) {
                    $indexingEntity->setIsIndexable(true);
                }
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
            /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            /** @var FilterBuilder $filterBuilder */
            $filterBuilder = $this->filterBuilderFactory->create();
            /** @var FilterGroupBuilder $filterGroupBuilder */
            $filterGroupBuilder = $this->filterGroupBuilderFactory->create();

            $targetIdFilter = $filterBuilder
                ->setField(IndexingEntity::TARGET_ID)
                ->setConditionType('in')
                ->setValue($entityIds)
                ->create();

            $targetParentIdFilter = $filterBuilder
                ->setField(IndexingEntity::TARGET_PARENT_ID)
                ->setConditionType('in')
                ->setValue($entityIds)
                ->create();

            /**
             * @var FilterGroup $filterGroup
             */
            $filterGroup = $filterGroupBuilder->addFilter($targetIdFilter)
                ->addFilter($targetParentIdFilter)
                ->create();

            $searchCriteria = $searchCriteriaBuilder
                ->setFilterGroups([$filterGroup])
                ->create();

            $searchResult = $this->indexingEntityRepository->getList($searchCriteria);
            $indexingEntities = $searchResult->getItems();
        }

        return $indexingEntities;
    }
}
