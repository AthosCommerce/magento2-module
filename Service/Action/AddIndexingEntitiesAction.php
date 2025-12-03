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
use AthosCommerce\Feed\Service\Action\AddIndexingEntitiesActionInterface;
use AthosCommerce\Feed\Api\Data\IndexingEntityInterface;
use AthosCommerce\Feed\Model\Api\MagentoEntityInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Psr\Log\LoggerInterface;

class AddIndexingEntitiesAction implements AddIndexingEntitiesActionInterface
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
     * @param string $type
     * @param $magentoEntities
     *
     * @return void
     */
    public function execute(string $type, $magentoEntities): void
    {
        try {
            $magentoEntityIds = [];
            foreach ($magentoEntities as $magentoEntity) {
                $magentoEntityIds[] = $magentoEntity->getEntityId();

                $indexingEntity = $this->createIndexingEntity($type, $magentoEntity);
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
    }

    /**
     * @param string $type
     * @param MagentoEntityInterface $magentoEntity
     *
     * @return IndexingEntityInterface
     */
    private function createIndexingEntity(
        string $type,
        MagentoEntityInterface $magentoEntity,
    ): IndexingEntityInterface {
        $isIndexable = $magentoEntity->isIndexable();
        $indexingEntity = $this->indexingEntityRepository->create();
        $indexingEntity->setTargetEntityType($type);
        $indexingEntity->setTargetEntitySubtype($magentoEntity->getEntitySubtype());
        $indexingEntity->setTargetId($magentoEntity->getEntityId());
        $indexingEntity->setTargetParentId($magentoEntity->getEntityParentId());
        $indexingEntity->setSiteId($magentoEntity->getSiteId());
        $indexingEntity->setIsIndexable($isIndexable);
        $indexingEntity->setNextAction(
            $isIndexable
                ? \AthosCommerce\Feed\Model\Source\Actions::UPSERT
                : Actions::NO_ACTION,
        );
        $indexingEntity->setLastAction(Actions::NO_ACTION);

        return $indexingEntity;
    }

}
