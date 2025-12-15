<?php

namespace AthosCommerce\Feed\Service\Action;

use AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface;
use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Service\Action\SetIndexingEntitiesToBeIndexableActionInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\FilterBuilderFactory;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilderFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Psr\Log\LoggerInterface;

class SetIndexingEntitiesToBeIndexableAction implements SetIndexingEntitiesToBeIndexableActionInterface
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param IndexingEntityRepositoryInterface $indexingEntityRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param FilterBuilderFactory $filterBuilderFactory
     * @param FilterGroupBuilderFactory $filterGroupBuilderFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        IndexingEntityRepositoryInterface $indexingEntityRepository,
        SearchCriteriaBuilderFactory      $searchCriteriaBuilderFactory,
        FilterBuilderFactory              $filterBuilderFactory,
        FilterGroupBuilderFactory         $filterGroupBuilderFactory,
        LoggerInterface                   $logger
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
     *
     * @return void
     */
    public function execute(array $entityIds): void
    {
        $indexingEntities = $this->getIndexingEntities(iterator_to_array($entityIds));
        try {
            $indexingEntityIds = [];
            foreach ($indexingEntities as $indexingEntity) {
                if (
                    $indexingEntity->getIsIndexable()
                    && $indexingEntity->getNextAction() !== Actions::DELETE
                ) {
                    continue;
                }
                $indexingEntityIds[] = $indexingEntity->getId();

                $isNextActionUpdateRequired = in_array(
                    $indexingEntity->getLastAction(),
                    [Actions::NO_ACTION, Actions::DELETE],
                    true,
                );
                $indexingEntity->setNextAction(
                    $isNextActionUpdateRequired
                        ? Actions::UPSERT
                        : Actions::NO_ACTION,
                );
                $indexingEntity->setIsIndexable(true);
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
            unset($searchResult);
        }

        return $indexingEntities;
    }
}
