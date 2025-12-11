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

use AthosCommerce\Feed\Service\Action\UpdateIndexingEntitiesActionsActionInterface;
use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Api\Data\IndexingEntityInterface;
use AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface;
use AthosCommerce\Feed\Model\Source\Actions;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class UpdateIndexingEntitiesActionsAction implements UpdateIndexingEntitiesActionsActionInterface
{
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;
    /**
     * @var IndexingEntityRepositoryInterface
     */
    private $indexingEntityRepository;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param IndexingEntityRepositoryInterface $indexingEntityRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        IndexingEntityRepositoryInterface $indexingEntityRepository,
        LoggerInterface $logger
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->indexingEntityRepository = $indexingEntityRepository;
        $this->logger = $logger;
    }

    /**
     * @param array $entityIds
     * @param string $lastAction
     * @param string $fieldIdentifier
     *
     * @return void
     */
    public function execute(
        array $entityIds,
        string $siteId,
        string $lastAction,
        string $fieldIdentifier = IndexingEntity::ENTITY_ID
    ): void {
        $result = $this->indexingEntityRepository->getList(
            $this->getSearchCriteria($entityIds, $siteId, $lastAction, $fieldIdentifier),
        );
        foreach ($result->getItems() as $indexingEntity) {
            $this->updateIndexingEntity($indexingEntity, $lastAction);
        }
    }

    /**
     * @param array $entityIds
     * @param string $actionTaken
     * @param string $fieldIdentifier
     *
     * @return SearchCriteria
     */
    private function getSearchCriteria(
        array $entityIds,
        string $siteId,
        string $actionTaken,
        string $fieldIdentifier = IndexingEntity::ENTITY_ID
    ): SearchCriteria {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter(
            $fieldIdentifier,
            $entityIds,
            'in',
        );
        $searchCriteriaBuilder->addFilter(
            IndexingEntity::SITE_ID,
            $siteId,
            'eq'
        );
        $searchCriteriaBuilder->addFilter(
            IndexingEntity::IS_INDEXABLE,
            true,
        );
        if ($actionTaken === Actions::DELETE) {
            // When a product type changes in the admin (e.g. simple becomes configurable via Create Configurations)
            // we will have 2 entities with same id in athoscommerce_indexing_entity db table,
            // one to be added (configurable) and one to be deleted (simple)
            // if the action taken was Delete then we exclude products that have never been indexed
            // i.e. are waiting to be added
            $searchCriteriaBuilder->addFilter(
                IndexingEntity::LAST_ACTION,
                Actions::NO_ACTION,
                'neq',
            );
        }

        return $searchCriteriaBuilder->create();
    }

    /**
     * @param IndexingEntityInterface $indexingEntity
     * @param string $actionTaken
     *
     * @return void
     */
    private function updateIndexingEntity(
        IndexingEntityInterface $indexingEntity,
        string $actionTaken
    ): void {
        $indexingEntity->setLastAction($actionTaken);
        $indexingEntity->setLastActionTimestamp(date('Y-m-d H:i:s'));
        if ($indexingEntity->getNextAction() === $actionTaken) {
            $indexingEntity->setNextAction(Actions::NO_ACTION);
        }
        if ($actionTaken === Actions::DELETE) {
            $indexingEntity->setIsIndexable(false);
        }
        try {
            $this->indexingEntityRepository->save($indexingEntity);
        } catch (LocalizedException $exception) {
            $this->logger->error(
                'Method: {method}, Error: {message}',
                [
                    'method' => __METHOD__,
                    'message' => $exception->getMessage(),
                ],
            );
        }
    }
}
