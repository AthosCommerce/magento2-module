<?php

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Parent;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Collection as ParentProductCollection;
use AthosCommerce\Feed\Model\Feed\DataProvider\Option\Visibility;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type as MagentoProductType;
use Magento\Framework\EntityManager\MetadataPool;

class ConfigurableDataProvider implements DataProviderInterface
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;
    /**
     * @var RelationsProvider
     */
    private $relationsProvider;
    /**
     * @var ParentProductCollection
     */
    private $parentProductCollection;
    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * @param MetadataPool $metadataPool
     * @param RelationsProvider $relationsProvider
     * @param Collection $parentProductCollection
     * @param Visibility $visibility
     */
    public function __construct(
        MetadataPool $metadataPool,
        RelationsProvider $relationsProvider,
        ParentProductCollection $parentProductCollection,
        Visibility $visibility,
    ) {
        $this->metadataPool = $metadataPool;
        $this->relationsProvider = $relationsProvider;
        $this->parentProductCollection = $parentProductCollection;
        $this->visibility = $visibility;
    }

    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $childIds = $this->getChildIds($products);
        if (empty($childIds)) {
            return $products;
        }

        $parentChildIds = $this->relationsProvider->getConfigurableRelationIds($childIds);
        if (!$parentChildIds) {
            return $products;
        }

        $childToParent = [];
        $allParentIds = [];
        foreach ($parentChildIds as $relationRow) {
            if (!isset($relationRow['product_id'], $relationRow['parent_id'])) {
                continue;
            }

            $childId = (int)$relationRow['product_id'];
            $parentId = (int)$relationRow['parent_id'];

            $childToParent[$childId][] = $parentId;
            $allParentIds[] = $parentId;
        }

        $uniqueParentIds = array_values(array_unique($allParentIds));
        $parentCollection = $this->parentProductCollection->execute(
            $uniqueParentIds,
            $feedSpecification
        );

        $parentsDataById = [];
        /** @var ProductInterface $parentProduct */
        foreach ($parentCollection as $parentProduct) {
            $parentsDataById[(int)$parentProduct->getId()] = $parentProduct;
        }
        unset($parentChildIds);

        $linkField = $this->getLinkField();

        foreach ($products as $productIndex => &$product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }
            $childEntityId = (int)$productModel->getData($linkField);
            $parentIds = $childToParent[$childEntityId] ?? [];
            if (empty($parentIds)) {
                continue;
            }
            $parent = null;
            foreach ($parentIds as $parentId) {
                //first parent
                if (isset($parentsDataById[$parentId])) {
                    $parent = $parentsDataById[$parentId];
                    break;
                }
            }
            if (!$parent) {
                continue;
            }

            //TODO:: Refactor via separate provider as needed
            $shouldExclude = $this->shouldExcludeProduct($productModel, $product, $parent);
            if ($shouldExclude) {
                unset($products[$productIndex]);
            }

            if (in_array(
                $productModel->getTypeId(),
                [MagentoProductType::TYPE_SIMPLE, MagentoProductType::TYPE_VIRTUAL],
                true
            )) {
                if (array_key_exists('parent_ids', $product)) {
                    $product['parent_ids'] = array_values(
                        array_unique(
                            array_merge(
                                $product['parent_ids'],
                                $parentIds
                            )
                        )
                    );
                } else {
                    $product['parent_ids'] = $parentIds;
                }
                if (method_exists($parent, 'getTypeId') && $parent->getTypeId()) {
                    $product['parent_type_id'] = $parent->getTypeId();
                }
                if (method_exists($parent, 'getStatus') && $parent->getStatus()) {
                    $product['parent_status'] = $parent->getStatus();
                }
                if (method_exists($parent, 'getProductUrl') && $parent->getProductUrl()) {
                    $product['url'] = $parent->getProductUrl();
                }
                if (method_exists($parent, 'getVisibility') && $parent->getVisibility()) {
                    $product['visibility'] = $this->visibility->getVisibilityTextValue(
                        $parent->getVisibility()
                    );
                }
            }
        }
        unset($childToParent);
        unset($parentsDataById);

        return array_values($products);
    }

    /**
     * @param array $product
     * @param ProductInterface $parent
     *
     * @return bool
     */
    private function shouldExcludeProduct(
        Product $productModel,
        array $product,
        ProductInterface $parent
    ): bool {
        $isExclude = false;
        if (($productModel->getVisibility() == 1 && $parent->getVisibility() == 1)
            || (
                $parent->getStatus() == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED
                && $productModel->getVisibility() == 1
            )
        ) {
            $isExclude = true;
        }

        return $isExclude;
    }

    /**
     * @param array $products
     *
     * @return array
     */
    public function getChildIds(array $products): array
    {
        $childIds = [];
        foreach ($products as $product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }
            //TODO:: If any product types are belongs to parents. check for composites and 3rd Party
            if (MagentoProductType::TYPE_SIMPLE === $productModel->getTypeId()
                || MagentoProductType::TYPE_VIRTUAL === $productModel->getTypeId()
            ) {
                $childIds[] = $productModel->getId();
            }
        }

        return array_filter($childIds);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getLinkField(): string
    {
        return $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        //do nothing
    }

    /**
     * @return void
     */
    public function resetAfterFetchItems(): void
    {
        // do nothing
    }
}
