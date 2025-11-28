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

namespace AthosCommerce\Feed\Service\Provider;

use AthosCommerce\Feed\Service\Provider\Api\IndexingEntityProviderInterface;
use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Model\ResourceModel\IndexingEntity\Collection;
use AthosCommerce\Feed\Model\ResourceModel\IndexingEntity\CollectionFactory;
use AthosCommerce\Feed\Api\Data\IndexingEntityInterface;
use AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface;
use AthosCommerce\Feed\Model\Source\Actions;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\FilterBuilderFactory;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilderFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\SortOrderBuilderFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;

class IndexingEntityProvider implements IndexingEntityProviderInterface
{
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory;
    /**
     * @var IndexingEntityRepositoryInterface
     */
    private IndexingEntityRepositoryInterface $indexingEntityRepository;
    /**
     * @var FilterGroupBuilderFactory
     */
    private FilterGroupBuilderFactory $filterGroupBuilderFactory;
    /**
     * @var FilterBuilderFactory
     */
    private FilterBuilderFactory $filterBuilderFactory;
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;
    /**
     * @var SortOrderBuilderFactory
     */
    private SortOrderBuilderFactory $sortOrderBuilderFactory;

    /**
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param IndexingEntityRepositoryInterface $indexingEntityRepository
     * @param FilterGroupBuilderFactory $filterGroupBuilderFactory
     * @param FilterBuilderFactory $filterBuilderFactory
     * @param CollectionFactory $collectionFactory
     * @param ResourceConnection $resourceConnection
     * @param SortOrderBuilderFactory $sortOrderBuilderFactory
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        IndexingEntityRepositoryInterface $indexingEntityRepository,
        FilterGroupBuilderFactory $filterGroupBuilderFactory,
        FilterBuilderFactory $filterBuilderFactory,
        CollectionFactory $collectionFactory,
        ResourceConnection $resourceConnection,
        SortOrderBuilderFactory $sortOrderBuilderFactory,
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->indexingEntityRepository = $indexingEntityRepository;
        $this->filterGroupBuilderFactory = $filterGroupBuilderFactory;
        $this->filterBuilderFactory = $filterBuilderFactory;
        $this->collectionFactory = $collectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->sortOrderBuilderFactory = $sortOrderBuilderFactory;
    }

    /**
     * Note: as sortOrder is required for pagination to work correctly,
     *  if $pageSize is provided then $sorting is ignored and collection is sorted by IndexingEntity::ENTITY_ID
     *
     * @param string|null $entityType
     * @param string[]|null $siteIds
     * @param int[]|null $entityIds
     * @param Actions|null $nextAction
     * @param bool|null $isIndexable
     * @param array<string, string>|null $sorting [SortOrder::DIRECTION => SortOrder::SORT_ASC, SortOrder::FIELD => '']
     * @param int|null $pageSize
     * @param int|null $startFrom
     * @param string[]|null $entitySubtypes
     *
     * @return IndexingEntityInterface[]
     */
    public function get(
        ?string $entityType = null,
        ?array $siteIds = [],
        ?array $entityIds = [],
        ?Actions $nextAction = null,
        ?bool $isIndexable = null,
        ?array $sorting = [],
        ?int $pageSize = null,
        ?int $startFrom = 1,
        ?array $entitySubtypes = [],
    ): array {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        if ($entityIds) {
            /** @var FilterBuilder $filterBuilder */
            $filterBuilder = $this->filterBuilderFactory->create();
            $filterBuilder->setField(IndexingEntity::TARGET_ID);
            $filterBuilder->setValue($entityIds);
            $filterBuilder->setConditionType('in');
            $filter1 = $filterBuilder->create();

            /** @var FilterGroupBuilder $filterGroupBuilder */
            $filterGroupBuilder = $this->filterGroupBuilderFactory->create();
            $filterGroupBuilder->addFilter($filter1);

            /** @var FilterGroup $filterOr */
            $filterOr = $filterGroupBuilder->create();

            $searchCriteriaBuilder->setFilterGroups([$filterOr]);
        }
        if ($entitySubtypes) {
            $searchCriteriaBuilder->addFilter(
                IndexingEntity::TARGET_ENTITY_SUBTYPE,
                $entitySubtypes,
                'in',
            );
        }
        if ($entityType) {
            $searchCriteriaBuilder->addFilter(
                IndexingEntity::TARGET_ENTITY_TYPE,
                $entityType,
            );
        }
        if ($siteIds) {
            $searchCriteriaBuilder->addFilter(
                IndexingEntity::SITE_ID,
                $siteIds,
                'in',
            );
        }
        if ($nextAction) {
            $searchCriteriaBuilder->addFilter(
                IndexingEntity::NEXT_ACTION,
                $nextAction->value,
            );
        }
        if (null !== $isIndexable) {
            $searchCriteriaBuilder->addFilter(
                IndexingEntity::IS_INDEXABLE,
                $isIndexable,
            );
        }
        if (null !== $pageSize) {
            $searchCriteriaBuilder->setPageSize($pageSize);
            $searchCriteriaBuilder->addFilter(
                IndexingEntity::ENTITY_ID,
                $startFrom,
                'gteq',
            );
            /** @var SortOrderBuilder $sortOrderBuilder */
            $sortOrderBuilder = $this->sortOrderBuilderFactory->create();
            $sortOrderBuilder->setField(IndexingEntity::ENTITY_ID);
            $sortOrderBuilder->setDirection(SortOrder::SORT_ASC);
            $searchCriteriaBuilder->addSortOrder(sortOrder: $sortOrderBuilder->create());
        } elseif (($sorting[SortOrder::FIELD] ?? null) && ($sorting[SortOrder::DIRECTION] ?? null)) {
            /** @var SortOrderBuilder $sortOrderBuilder */
            $sortOrderBuilder = $this->sortOrderBuilderFactory->create();
            $sortOrderBuilder->setField($sorting[SortOrder::FIELD]);
            $sortOrderBuilder->setDirection(strtoupper($sorting[SortOrder::DIRECTION]));
            $searchCriteriaBuilder->addSortOrder(sortOrder: $sortOrderBuilder->create());
        }
        $searchCriteria = $searchCriteriaBuilder->create();
        $entitySearchResult = $this->indexingEntityRepository->getList($searchCriteria);
        $return = $entitySearchResult->getItems();
        unset($entitySearchResult);

        return $return;
    }

    /**
     * @param string|null $entityType
     * @param string|null $siteId
     * @param int[][]|null $pairs
     *
     * @return Collection
     */
    public function getForTargetParentPairs(
        ?string $entityType = null,
        ?string $siteId = null,
        ?array $pairs = [],
    ): Collection {
        // Can't use repository and searchCriteria here due to nature of required query structure.
        // Required: (a=b AND c=d) OR (e=f AND g=h).
        // only "(a=b OR c=d) AND (e=f OR g=h)" and "a=b AND c=d AND e=f AND g=h" are possible with searchCriteria.

        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $select = $collection->getSelect();
        $select->reset(Select::COLUMNS);
        $collection->addFieldToSelect('*');
        if ($pairs) {
            $this->addTargetAndParentFieldsToFilter($pairs, $collection);
        }
        if ($siteId) {
            $collection->addFieldToFilter(IndexingEntity::SITE_ID, ['eq' => $siteId]);
        }
        if ($entityType) {
            $collection->addFieldToFilter(IndexingEntity::TARGET_ENTITY_TYPE, ['eq' => $entityType]);
        }

        return $collection;
    }

    /**
     * @param string|null $entityType
     * @param string|null $siteId
     * @param Actions|null $nextAction
     * @param bool|null $isIndexable
     *
     * @return int
     */
    public function count(
        ?string $entityType = null,
        ?string $siteId = null,
        ?Actions $nextAction = null,
        ?bool $isIndexable = null,
    ): int {
        return $this->indexingEntityRepository->count(
            $entityType,
            $siteId,
            $nextAction,
            $isIndexable,
        );
    }

    /**
     * @param string $siteId
     *
     * @return string[]
     */
    public function getTypes(string $siteId): array
    {
        return $this->indexingEntityRepository->getUniqueEntityTypes($siteId);
    }

    /**
     * @param int[][] $pairs
     * @param Collection $collection
     *
     * @return void
     */
    private function addTargetAndParentFieldsToFilter(array $pairs, Collection $collection): void
    {
        $connection = $this->resourceConnection->getConnection();
        $conditions = [];
        foreach ($pairs as $pair) {
            $conditions[] = $this->buildConditionForEntityPairs($pair, $connection);
        }
        $select = $collection->getSelect();
        $select->where( // We have already escaped input
            implode(
                ' OR ',
                $conditions, // phpcs:ignore Security.Drupal7.DynQueries.D7DynQueriesDirectVar
            ),
        );
    }

    /**
     * @param int[] $pair
     * @param AdapterInterface $connection
     *
     * @return string
     */
    private function buildConditionForEntityPairs(array $pair, AdapterInterface $connection): string
    {
        $condition = $connection->quoteInto(
            '(`target_id` = ? AND ',
            $pair[IndexingEntity::TARGET_ID],
        );
        $condition .= (($pair[IndexingEntity::TARGET_PARENT_ID] ?? null)
            ? $connection->quoteInto(
                '`target_parent_id` = ?)',
                $pair[IndexingEntity::TARGET_PARENT_ID],
            )
            : '`target_parent_id` IS NULL)');

        return $condition;
    }
}
