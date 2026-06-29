<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Context;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\RelationsProvider;
use Magento\Catalog\Api\Data\ProductInterface;

class ParentRelationsContext
{
    /**
     * @var RelationsProvider
     */
    private $relationsProvider;

    /**
     * @var ParentDataContextManager
     */
    private $parentDataContextManager;

    /**
     * Cache: [childId => [parentIds]]
     *
     * @var array<int, int[]>
     */
    private $childToParentMap = [];

    /**
     * @param RelationsProvider $relationsProvider
     * @param ParentDataContextManager $parentDataContextManager
     */
    public function __construct(
        RelationsProvider        $relationsProvider,
        ParentDataContextManager $parentDataContextManager
    )
    {
        $this->relationsProvider = $relationsProvider;
        $this->parentDataContextManager = $parentDataContextManager;
    }

    /**
     * @param array $childIds
     * @param FeedSpecificationInterface $feedSpecification
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function buildContext(
        array                      $childIds,
        FeedSpecificationInterface $feedSpecification
    ): void
    {
        $childIds = array_values(array_unique(array_map('intval', $childIds)));
        $this->loadRelations($childIds);

        $allParentIds = [];
        foreach ($childIds as $childId) {
            foreach ($this->childToParentMap[$childId] ?? [] as $parentId) {
                $allParentIds[] = $parentId;
            }
        }

        $allParentIds = array_values(array_unique($allParentIds));
        if ($allParentIds) {
            $this->parentDataContextManager->execute($allParentIds, $feedSpecification);
        }
    }

    /**
     * Backward-compatible API: returns the first available parent.
     */
    public function getParentsByChildId(int $childId): ?ProductInterface
    {
        $parents = $this->getAllParentsByChildId($childId);

        return $parents[0] ?? null;
    }

    /**
     * Return all resolved parent products for a child.
     *
     * @param int $childId
     * @return ProductInterface[]
     */
    public function getAllParentsByChildId(int $childId): array
    {
        if (empty($this->childToParentMap[$childId])) {
            return [];
        }

        $parents = [];
        foreach ($this->childToParentMap[$childId] as $parentId) {
            $parentData = $this->parentDataContextManager->getParentsDataByProductId((int)$parentId);

            if (!$parentData instanceof ProductInterface) {
                continue;
            }

            $parents[] = $parentData;
        }

        return $parents;
    }

    /**
     * @param int $childId
     * @return bool
     * @throws \Exception
     */
    public function hasParentRelation(int $childId): bool
    {
        if (!array_key_exists($childId, $this->childToParentMap)) {
            $this->loadRelations([$childId]);
        }

        return !empty($this->childToParentMap[$childId]);
    }

    /**
     * @param array $childIds
     * @return void
     * @throws \Exception
     */
    private function loadRelations(array $childIds): void
    {
        $childIds = array_values(array_unique(array_map('intval', $childIds)));
        $missingChildIds = array_values(array_filter($childIds, function (int $childId): bool {
            return !array_key_exists($childId, $this->childToParentMap);
        }));

        if ($missingChildIds === []) {
            return;
        }

        foreach ($missingChildIds as $childId) {
            $this->childToParentMap[$childId] = [];
        }

        $configurableRelations = $this->relationsProvider->getConfigurableRelationIds($missingChildIds);
        $groupedRelations = $this->relationsProvider->getGroupRelationIds($missingChildIds);

        foreach (array_merge($configurableRelations, $groupedRelations) as $row) {
            if (!isset($row['product_id'], $row['parent_id'])) {
                continue;
            }

            $childId = (int)$row['product_id'];
            $parentId = (int)$row['parent_id'];
            $this->childToParentMap[$childId][] = $parentId;
        }

        foreach ($this->childToParentMap as $childId => $parentIds) {
            $this->childToParentMap[$childId] = array_values(array_unique(array_map('intval', $parentIds)));
        }
    }

    /**
     * Resolve exact parent by child + parentSku + productType.
     *
     * Matching order:
     * 1. childId + parentSku + productType
     * 2. childId + parentSku
     * 3. childId + productType
     * 4. first available parent
     *
     * @param int $childId
     * @param string|null $parentSku
     * @param string|null $productType
     *
     * @return ProductInterface|null
     */
    public function getParentByChildSkuAndType(
        int     $childId,
        ?string $parentSku,
        ?string $productType
    ): ?ProductInterface
    {
        if (empty($this->childToParentRelations[$childId])) {
            return null;
        }

        $normalizedParentSku = $parentSku !== null ? trim($parentSku) : '';
        $normalizedProductType = $productType !== null ? trim($productType) : '';

        $relations = $this->childToParentRelations[$childId];

        foreach ($relations as $relation) {
            if ($normalizedParentSku === '' || $normalizedProductType === '') {
                break;
            }

            if ((string)$relation['parent_sku'] !== $normalizedParentSku) {
                continue;
            }

            if ((string)$relation['parent_type_id'] !== $normalizedProductType) {
                continue;
            }

            $parent = $this->parentDataContextManager->getParentsDataByProductId((int)$relation['parent_id']);
            if ($parent instanceof ProductInterface) {
                return $parent;
            }
        }

        foreach ($relations as $relation) {
            if ($normalizedParentSku === '') {
                break;
            }

            if ((string)$relation['parent_sku'] !== $normalizedParentSku) {
                continue;
            }

            $parent = $this->parentDataContextManager->getParentsDataByProductId((int)$relation['parent_id']);
            if ($parent instanceof ProductInterface) {
                return $parent;
            }
        }

        foreach ($relations as $relation) {
            if ($normalizedProductType === '') {
                break;
            }

            if ((string)$relation['parent_type_id'] !== $normalizedProductType) {
                continue;
            }

            $parent = $this->parentDataContextManager->getParentsDataByProductId((int)$relation['parent_id']);
            if ($parent instanceof ProductInterface) {
                return $parent;
            }
        }

        return $this->getParentsByChildId($childId);
    }

    /**
     * @param int $childId
     *
     * @return array<int, array<string, int|string>>
     */
    public function getParentRelationsByChildId(int $childId): array
    {
        return $this->childToParentRelations[$childId] ?? [];
    }


    /**
     * @return void
     */
    public function reset(): void
    {
        $this->childToParentMap = [];
        $this->parentDataContextManager->resetContext();
    }
}
