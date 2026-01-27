<?php

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Parent;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentDataContextManager;
use AthosCommerce\Feed\Model\Feed\DataProvider\Option\Visibility;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use AthosCommerce\Feed\Model\Feed\ProductExclusionInterface;
use AthosCommerce\Feed\Model\Feed\ProductTypeIdInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type as MagentoProductType;
use Magento\Framework\EntityManager\MetadataPool;

class GroupedDataProvider implements DataProviderInterface
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
        MetadataPool              $metadataPool,
        RelationsProvider         $relationsProvider,
        ParentDataContextManager  $parentProductContextManager,
        Visibility                $visibility,
        ProductExclusionInterface $productExclusion,
        ProductTypeIdInterface    $productTypeId
    )
    {
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
        array                      $products,
        FeedSpecificationInterface $feedSpecification
    ): array
    {
        $childTypeIds = $this->productTypeId->getChildTypeIdsList();
        $childEntityIds = $this->getChildIds($products, $childTypeIds);

        if (!$childEntityIds) {
            return $products;
        }

        $relations = $this->relationsProvider->getGroupRelationIds($childEntityIds);
        if (!$relations) {
            return $products;
        }

        $ignoredFields = $feedSpecification->getIgnoreFields();
        $linkField = $this->getLinkField();

        $childEntityToLink = [];
        foreach ($products as $product) {
            $productModel = $product['product_model'] ?? null;
            if ($productModel) {
                $childEntityToLink[(int)$productModel->getId()] =
                    (int)$productModel->getData($linkField);
            }
        }

        $childToParent = [];
        foreach ($relations as $row) {
            if (!isset($row['product_id'], $row['parent_id'])) {
                continue;
            }

            $childEntityId = (int)$row['product_id'];
            $childLinkId = $childEntityToLink[$childEntityId] ?? null;

            if (!$childLinkId) {
                continue;
            }

            $childToParent[$childLinkId][(int)$row['parent_id']] = true;
        }

        $finalProducts = [];

        foreach ($products as $product) {
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            $childLinkId = (int)$productModel->getData($linkField);
            $parentLinkIds = array_keys($childToParent[$childLinkId] ?? []);

            if (!$parentLinkIds) {
                $finalProducts[] = $product;
                continue;
            }

            foreach ($parentLinkIds as $parentId) {
                $parent = $this->parentProductContextManager
                    ->getParentsDataByProductId((int)$parentId);

                if (!$parent) {
                    continue;
                }

                if ($this->productExclusion->shouldExclude(
                    $feedSpecification,
                    $productModel,
                    $parent
                )) {
                    continue;
                }

                $childClone = $product;

                if (in_array($productModel->getTypeId(), $childTypeIds, true)) {

                    if (!in_array(['__parent_id', 'parent_id'], $ignoredFields, true)) {
                        $childClone['__parent_id'] = $parent->getDataUsingMethod($this->getLinkField());
                    }

                    if (!in_array(['__parent_title', 'parent_title'], $ignoredFields, true)
                        && method_exists($parent, 'getName')
                        && $parent->getName()
                    ) {
                        $childClone['__parent_title'] = $parent->getName();
                    }

                    if (!in_array('parent_status', $ignoredFields, true)
                        && method_exists($parent, 'getStatus')
                    ) {
                        $childClone['parent_status'] = $parent->getStatus()
                            ? __('Enabled')->getText()
                            : __('Disabled')->getText();
                    }

                    if (!in_array('parent_type_id', $ignoredFields, true)
                        && method_exists($parent, 'getTypeId')
                    ) {
                        $childClone['parent_type_id'] = $parent->getTypeId();
                    }

                    if (!in_array('parent_url', $ignoredFields, true)
                        && method_exists($parent, 'getProductUrl')
                        && $parent->getProductUrl()
                    ) {
                        $childClone['parent_url'] = $parent->getProductUrl();
                    }

                    if (!in_array('parent_visibility', $ignoredFields, true)
                        && method_exists($parent, 'getVisibility')
                        && $parent->getVisibility()
                    ) {
                        $childClone['parent_visibility'] =
                            $this->visibility->getVisibilityTextValue(
                                $parent->getVisibility()
                            );
                    }

                    $parentImage = '';
                    if (!in_array(['parent_image', '__parent_image'], $ignoredFields, true)) {
                        $image = $parent->getImage()
                            ?: $parent->getSmallImage()
                                ?: $parent->getThumbnail();
                        if ($image && $image !== 'no_selection') {
                            $parentImage = $parent->getMediaConfig()->getMediaUrl($image);
                        }
                        $childClone['__parent_image'] = $parentImage;
                    }
                }

                $finalProducts[] = $childClone;
            }
        }

        return array_values($finalProducts);
    }

    /**
     * @param array $products
     * @param array $childTypeIdsList
     * @return array
     */
    public function getChildIds(array $products, array $childTypeIdsList): array
    {
        $childIds = [];
        foreach ($products as $product) {
            $productModel = $product['product_model'] ?? null;
            if ($productModel
                && in_array($productModel->getTypeId(), $childTypeIdsList, true)
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
        return $this->metadataPool
            ->getMetadata(ProductInterface::class)
            ->getLinkField();
    }

    /**
     * Reset internal state after feed generation is complete
     */
    public function reset(): void
    {

    }

    /**
     * Reset internal state after fetching items batch
     */
    public function resetAfterFetchItems(): void
    {

    }
}
