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
use AthosCommerce\Feed\Api\Data\EntityTrackingListResponseInterface;
use AthosCommerce\Feed\Api\Data\EntityTrackingListResponseInterfaceFactory;
use AthosCommerce\Feed\Api\Data\IndexingEntityInterface;
use AthosCommerce\Feed\Api\GetEntityListInterface;
use AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface;
use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Model\Source\Actions;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilderFactory;

class GetEntityList implements GetEntityListInterface
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
     * @var \AthosCommerce\Feed\Api\Data\EntityTrackingListResponseInterfaceFactory
     */
    private EntityTrackingListResponseInterfaceFactory $responseFactory;

    /**
     * @var \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterfaceFactory
     */
    private EntityTrackingItemInterfaceFactory $itemFactory;

    /**
     * @var \AthosCommerce\Feed\Model\Api\EntityTrackingStatusResolver
     */
    private EntityTrackingStatusResolver $statusResolver;

    /**
     * Initialize the entity list builder dependencies.
     *
     * @param \AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface $indexingEntityRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Magento\Framework\Api\SortOrderBuilderFactory $sortOrderBuilderFactory
     * @param \AthosCommerce\Feed\Api\Data\EntityTrackingListResponseInterfaceFactory $responseFactory
     * @param \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterfaceFactory $itemFactory
     * @param \AthosCommerce\Feed\Model\Api\EntityTrackingStatusResolver $statusResolver
     */
    public function __construct(
        IndexingEntityRepositoryInterface          $indexingEntityRepository,
        SearchCriteriaBuilderFactory               $searchCriteriaBuilderFactory,
        SortOrderBuilderFactory                    $sortOrderBuilderFactory,
        EntityTrackingListResponseInterfaceFactory $responseFactory,
        EntityTrackingItemInterfaceFactory         $itemFactory,
        EntityTrackingStatusResolver               $statusResolver
    ) {
        $this->indexingEntityRepository = $indexingEntityRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->sortOrderBuilderFactory = $sortOrderBuilderFactory;
        $this->responseFactory = $responseFactory;
        $this->itemFactory = $itemFactory;
        $this->statusResolver = $statusResolver;
    }

    /**
     * Retrieve tracked entities with pagination and optional filters.
     *
     * @param int $currentPage
     * @param int $pageSize
     * @param int|null $targetId
     * @param int|null $targetParentId
     * @param string|null $entityType
     * @param string|null $siteId
     * @param string|null $status
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingListResponseInterface
     */
    public function getList(
        int     $currentPage = 1,
        int     $pageSize = 20,
        ?int    $targetId = null,
        ?int    $targetParentId = null,
        ?string $entityType = null,
        ?string $siteId = null,
        ?string $status = null
    ): EntityTrackingListResponseInterface {
        $normalizedStatus = $status !== null ? strtolower(trim($status)) : null;
        $currentPage = max(1, $currentPage);
        $pageSize = max(1, $pageSize);

        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->setCurrentPage($currentPage);
        $searchCriteriaBuilder->setPageSize($pageSize);

        if ($targetId !== null) {
            $searchCriteriaBuilder->addFilter(IndexingEntity::TARGET_ID, $targetId, 'eq');
        }
        if ($targetParentId !== null && $targetParentId > 0) {
            $searchCriteriaBuilder->addFilter(IndexingEntity::TARGET_PARENT_ID, $targetParentId, 'eq');
        }
        if ($entityType !== null && $entityType !== '') {
            $normalizedEntityType = $this->normalizeEntityType($entityType);
            $searchCriteriaBuilder->addFilter(IndexingEntity::TARGET_ENTITY_TYPE, $normalizedEntityType, 'eq');
        }
        if ($siteId !== null && $siteId !== '') {
            $searchCriteriaBuilder->addFilter(IndexingEntity::SITE_ID, $siteId, 'eq');
        }
        $this->applyStatusFilter($searchCriteriaBuilder, $normalizedStatus);

        $sortOrder = $this->sortOrderBuilderFactory->create()
            ->setField(IndexingEntity::UPDATED_AT)
            ->setDirection(SortOrder::SORT_DESC)
            ->create();
        $searchCriteriaBuilder->addSortOrder($sortOrder);

        $searchResult = $this->indexingEntityRepository->getList(
            $searchCriteriaBuilder->create(),
            true
        );

        $items = [];
        foreach ($searchResult->getItems() as $entity) {
            $items[] = $this->buildItem($entity);
        }

        $response = $this->responseFactory->create();
        $response->setItems($items);
        $response->setTotal((int)$searchResult->getTotalCount());
        $response->setCurrentPage($currentPage);
        $response->setPageSize($pageSize);

        return $response;
    }

    /**
     * Apply DB-level filters for supported tracking statuses.
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param string|null $status
     *
     * @return void
     */
    private function applyStatusFilter(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        ?string                                      $status
    ): void {
        if ($status === null || $status === '') {
            return;
        }

        switch ($status) {
            case EntityTrackingStatusResolver::STATUS_DELETED:
                $searchCriteriaBuilder->addFilter(IndexingEntity::IS_INDEXABLE, 0, 'eq');
                break;

            case EntityTrackingStatusResolver::STATUS_PROCESSING:
                $searchCriteriaBuilder->addFilter(IndexingEntity::IS_INDEXABLE, 1, 'eq');
                $searchCriteriaBuilder->addFilter(IndexingEntity::LOCK_TIMESTAMP, true, 'notnull');
                break;

            case EntityTrackingStatusResolver::STATUS_PENDING:
                $searchCriteriaBuilder->addFilter(IndexingEntity::IS_INDEXABLE, 1, 'eq');
                $searchCriteriaBuilder->addFilter(IndexingEntity::LOCK_TIMESTAMP, true, 'null');
                $searchCriteriaBuilder->addFilter(IndexingEntity::NEXT_ACTION, Actions::NO_ACTION, 'neq');
                break;

            case EntityTrackingStatusResolver::STATUS_SUCCESS:
                $searchCriteriaBuilder->addFilter(IndexingEntity::IS_INDEXABLE, 1, 'eq');
                $searchCriteriaBuilder->addFilter(IndexingEntity::LOCK_TIMESTAMP, true, 'null');
                $searchCriteriaBuilder->addFilter(IndexingEntity::NEXT_ACTION, Actions::NO_ACTION, 'eq');
                $searchCriteriaBuilder->addFilter(IndexingEntity::LAST_ACTION, Actions::NO_ACTION, 'neq');
                break;

            case EntityTrackingStatusResolver::STATUS_FAILED:
                $searchCriteriaBuilder->addFilter(IndexingEntity::IS_INDEXABLE, 1, 'eq');
                $searchCriteriaBuilder->addFilter(IndexingEntity::LOCK_TIMESTAMP, true, 'null');
                $searchCriteriaBuilder->addFilter(IndexingEntity::NEXT_ACTION, Actions::NO_ACTION, 'eq');
                $searchCriteriaBuilder->addFilter(IndexingEntity::LAST_ACTION, Actions::NO_ACTION, 'eq');
                break;
        }
    }

    /**
     * Build API DTO for a list item.
     *
     * @param \AthosCommerce\Feed\Api\Data\IndexingEntityInterface $entity
     *
     * @return \AthosCommerce\Feed\Api\Data\EntityTrackingItemInterface
     */
    private function buildItem(IndexingEntityInterface $entity): EntityTrackingItemInterface
    {
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
        $item->setLastApiResponse(null);
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
