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

namespace AthosCommerce\Feed\Model\Api;

use AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface;
use AthosCommerce\Feed\Api\Data\EntityTrackingItemInterfaceFactory;
use AthosCommerce\Feed\Api\Data\IndexingEntityInterface;
use AthosCommerce\Feed\Api\GetEntityDetailInterface;
use AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface;
use AthosCommerce\Feed\Model\IndexingEntity;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilderFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class GetEntityDetail implements GetEntityDetailInterface
{
    /**
     * @var \AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface
     */
    private IndexingEntityRepositoryInterface $indexingEntityRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilderFactory
     */
    private SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory;

    /**
     * @var \Magento\Framework\Api\SortOrderBuilderFactory
     */
    private SortOrderBuilderFactory $sortOrderBuilderFactory;

    /**
     * @var \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterfaceFactory
     */
    private EntityTrackingItemInterfaceFactory $itemFactory;

    /**
     * @var \AthosCommerce\Feed\Model\Api\EntityTrackingStatusResolver
     */
    private EntityTrackingStatusResolver $statusResolver;

    /**
     * Initialize the entity detail lookup dependencies.
     *
     * @param \AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface $indexingEntityRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Magento\Framework\Api\SortOrderBuilderFactory $sortOrderBuilderFactory
     * @param \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterfaceFactory $itemFactory
     * @param \AthosCommerce\Feed\Model\Api\EntityTrackingStatusResolver $statusResolver
     */
    public function __construct(
        IndexingEntityRepositoryInterface $indexingEntityRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        SortOrderBuilderFactory $sortOrderBuilderFactory,
        EntityTrackingItemInterfaceFactory $itemFactory,
        EntityTrackingStatusResolver $statusResolver
    ) {
        $this->indexingEntityRepository = $indexingEntityRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->sortOrderBuilderFactory = $sortOrderBuilderFactory;
        $this->itemFactory = $itemFactory;
        $this->statusResolver = $statusResolver;
    }

    /**
     * Retrieve the latest tracked record for the supplied entity identity.
     *
     * @param string $entityType
     * @param int $targetId
     * @param int $targetParentId
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(string $entityType, int $targetId, int $targetParentId = 0): EntityTrackingItemInterface
    {
        $normalizedEntityType = $this->normalizeEntityType($entityType);

        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter(IndexingEntity::TARGET_ENTITY_TYPE, $normalizedEntityType, 'eq');
        $searchCriteriaBuilder->addFilter(IndexingEntity::TARGET_ID, $targetId, 'eq');
        if ($targetParentId > 0) {
            $searchCriteriaBuilder->addFilter(IndexingEntity::TARGET_PARENT_ID, $targetParentId, 'eq');
        }
        $searchCriteriaBuilder->setCurrentPage(1);
        $searchCriteriaBuilder->setPageSize(1);

        $sortOrder = $this->sortOrderBuilderFactory->create()
            ->setField(IndexingEntity::UPDATED_AT)
            ->setDirection(SortOrder::SORT_DESC)
            ->create();
        $searchCriteriaBuilder->addSortOrder($sortOrder);

        $searchResult = $this->indexingEntityRepository->getList(
            $searchCriteriaBuilder->create()
        );
        $entity = current($searchResult->getItems());

        if (!$entity instanceof IndexingEntityInterface) {
            throw NoSuchEntityException::singleField('target_id', $targetId);
        }

        $item = $this->itemFactory->create();
        $item->setEntityId((int)$entity->getId());
        $item->setTargetId((int)$entity->getTargetId());
        $item->setEntityType($entity->getTargetEntityType());
        $item->setTargetEntitySubtype($entity->getTargetEntitySubtype());
        $item->setTargetParentId($entity->getTargetParentId());
        $item->setSiteId($entity->getSiteId());
        $item->setStatus($this->statusResolver->resolve($entity));
        $item->setNextAction($entity->getNextAction());
        $item->setLastAction($entity->getLastAction());
        $item->setLastActionTimestamp($entity->getLastActionTimestamp());
        $item->setLockTimestamp($entity->getLockTimestamp());
        $item->setIsIndexable($entity->getIsIndexable());
        $item->setLastApiResponse(null); // To be added in future if needed
        $item->setCreatedAt($entity->getCreatedAt());
        $item->setUpdatedAt($entity->getUpdatedAt());

        return $item;
    }

   /**
    * Normalize entity type values from user-friendly input to the database format.
    *
    * @param string $entityType
    *
    * @return string
    */
    private function normalizeEntityType(string $entityType): string
    {
        $normalizedEntityType = strtoupper(trim($entityType));

        if (strpos($normalizedEntityType, '__') !== 0) {
            $normalizedEntityType = '__' . $normalizedEntityType;
        }

        return $normalizedEntityType;
    }
}
