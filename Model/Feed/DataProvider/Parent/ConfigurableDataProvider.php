<?php

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Parent;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Collection as ParentProductCollection;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentDataContextManager;
use AthosCommerce\Feed\Model\Feed\DataProvider\Option\Visibility;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use AthosCommerce\Feed\Model\Feed\ProductExclusionInterface;
use AthosCommerce\Feed\Model\Feed\ProductTypeIdInterface;
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
     * @var ParentDataContextManager
     */
    private $parentProductContextManager;
    /**
     * @var Visibility
     */
    private $visibility;
    /**
     * @var ProductExclusionInterface
     */
    private $productExclusion;
    /**
     * @var ProductTypeIdInterface
     */
    private $productTypeId;

    /**
     * @param MetadataPool $metadataPool
     * @param RelationsProvider $relationsProvider
     * @param ParentDataContextManager $parentProductContextManager
     * @param Visibility $visibility
     * @param ProductExclusionInterface $productExclusion
     * @param ProductTypeIdInterface $productTypeId
     */
    public function __construct(
        MetadataPool $metadataPool,
        RelationsProvider $relationsProvider,
        ParentDataContextManager $parentProductContextManager,
        Visibility $visibility,
        ProductExclusionInterface $productExclusion,
        ProductTypeIdInterface $productTypeId
    ) {
        $this->metadataPool = $metadataPool;
        $this->relationsProvider = $relationsProvider;
        $this->parentProductContextManager = $parentProductContextManager;
        $this->visibility = $visibility;
        $this->productExclusion = $productExclusion;
        $this->productTypeId = $productTypeId;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     * @throws \Exception
     */
    public function getData(
        array $products,
        FeedSpecificationInterface $feedSpecification
    ): array {
        $childTypeIdsList = $this->productTypeId->getChildTypeIdsList();
        $childIds = $this->getChildIds($products, $childTypeIdsList);
        if (empty($childIds)) {
            return $products;
        }
        $parentChildIds = $this->relationsProvider->getConfigurableRelationIds($childIds);
        if (!$parentChildIds) {
            return $products;
        }

        //TODO:: Check needed against the each field.
        $ignoredFields = $feedSpecification->getIgnoreFields();

        $childToParent = [];
        $allParentIds = [];

        foreach ($parentChildIds as $parentChildRow) {
            if (!isset($parentChildRow['product_id'], $parentChildRow['parent_id'])) {
                continue;
            }

            $childId = (int)$parentChildRow['product_id'];
            $parentId = (int)$parentChildRow['parent_id'];

            $childToParent[$childId][] = $parentId;
            $allParentIds[] = $parentId;
        }

        $linkField = $this->getLinkField();
        $finalProducts = [];

        foreach ($products as $productIndex => &$product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }
            $childEntityId = (int)$productModel->getData($linkField);
            $parentIds = $childToParent[$childEntityId] ?? [];
            if (empty($parentIds)) {
                $finalProducts[] = $product;
                continue;
            }

            $parent = null;
            foreach ($parentIds as $parentId) {
                $parent = $this->parentProductContextManager->getParentsDataByProductId(
                    (int)$parentId,
                );
                if (!$parent) {
                    continue;
                }
                $shouldExclude = $this->productExclusion->shouldExclude($productModel, $parent);
                if ($shouldExclude) {
                    unset($products[$productIndex]);
                }
                $childClone = $product;

                if (in_array(
                    $productModel->getTypeId(),
                    $childTypeIdsList,
                    true
                )) {
                    $childClone['parent_id'] = $parent->getData($this->getLinkField());
                    if (method_exists($parent, 'getName') && $parent->getName()) {
                        $childClone['parent_name'] = $parent->getName();
                    }
                    if (method_exists($parent, 'getStatus')) {
                        $childClone['parent_status'] = $parent->getStatus()
                            ? __('Enabled')
                            : __('Disabled');
                    }
                    if (method_exists($parent, 'getTypeId')) {
                        $childClone['parent_type_id'] = $parent->getTypeId();
                    }
                    if (method_exists($parent, 'getProductUrl') && $parent->getProductUrl()) {
                        $childClone['parent_url'] = $parent->getProductUrl();
                    }
                    if (method_exists($parent, 'getVisibility') && $parent->getVisibility()) {
                        $childClone['parent_visibility'] = $this->visibility->getVisibilityTextValue(
                            $parent->getVisibility()
                        );
                    }
                    if (method_exists($parent, 'getImage') && $parent->getImage()) {
                        $childClone['parent_image'] = $parent->getImage();
                    }
                }
                $finalProducts[] = $childClone;
            }
        }
        unset($childToParent);

        return array_values($finalProducts);
    }

    /**
     * @param array $products
     *
     * @return array
     */
    public function getChildIds(array $products, array $childTypeIdsList): array
    {
        $childIds = [];
        foreach ($products as $product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }
            if (in_array($productModel->getTypeId(), $childTypeIdsList, true)) {
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
