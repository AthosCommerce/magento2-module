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
     * @var array<int, int[]>
     */
    private array $childToParentMap = [];

    /**
     * @param RelationsProvider $relationsProvider
     * @param ParentDataContextManager $parentDataContextManager
     */
    public function __construct(
        RelationsProvider $relationsProvider,
        ParentDataContextManager $parentDataContextManager
    ) {
        $this->relationsProvider = $relationsProvider;
        $this->parentDataContextManager = $parentDataContextManager;
    }

    /**
     * Build parent data context for given child products.
     *
     * @param int[] $childIds
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return void
     */
    public function buildContext(
        array $childIds,
        FeedSpecificationInterface $feedSpecification
    ): void {
        $childIds = array_values(array_unique(array_map('intval', $childIds)));

        // Get configurable + grouped relations
        $configurableRelations = $this->relationsProvider->getConfigurableRelationIds($childIds);
        $groupedRelations = $this->relationsProvider->getGroupRelationIds($childIds);

        $relations = array_merge($configurableRelations, $groupedRelations);

        if (empty($relations)) {
            return;
        }

        $allParentIds = [];
        foreach ($relations as $row) {
            if (!isset($row['product_id'], $row['parent_id'])) {
                continue;
            }
            $childId = (int)$row['product_id'];
            $parentId = (int)$row['parent_id'];

            $this->childToParentMap[$childId][] = $parentId;
            $allParentIds[] = $parentId;
        }

        $allParentIds = array_values(array_unique($allParentIds));

        if ($allParentIds) {
            $this->parentDataContextManager->execute($allParentIds, $feedSpecification);
        }
    }

    /**
     * Get parent product(s) for a given child product ID.
     *
     * @param int $childId
     *
     * @return ProductInterface|null
     */
    public function getParentsByChildId(int $childId): ?ProductInterface
    {
        if (empty($this->childToParentMap[$childId])) {
            return null;
        }

        $parent = null;
        foreach ($this->childToParentMap[$childId] as $parentId) {
            $parentData = $this->parentDataContextManager->getParentsDataByProductId($parentId);
            if (!$parentData instanceof ProductInterface) {
                continue;
            }

            return $parentData;
        }
        return $parent;
    }

    /**
     * Reset all cached context.
     */
    public function reset(): void
    {
        $this->childToParentMap = [];
        $this->parentDataContextManager->resetContext();
    }
}
