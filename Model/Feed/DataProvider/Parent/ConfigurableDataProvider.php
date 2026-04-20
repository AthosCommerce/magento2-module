<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Parent;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentDataContextManager;
use AthosCommerce\Feed\Model\Feed\DataProvider\Option\Visibility;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use AthosCommerce\Feed\Model\Feed\ProductExclusionInterface;
use AthosCommerce\Feed\Model\Feed\ProductTypeIdInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
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
     * @return array
     */
    public function getData(
        array                      $products,
        FeedSpecificationInterface $feedSpecification
    ): array
    {
        $childTypeIdsList = $this->productTypeId->getChildTypeIdsList();

        $childEntityIds = $this->getChildIds($products, $childTypeIdsList);
        if (!$childEntityIds) {
            return $products;
        }

        $relations = $this->relationsProvider->getConfigurableRelationIds($childEntityIds);
        if (!$relations) {
            return $products;
        }

        $linkField = $this->getLinkField();

        $childEntityToLinkMap = $this->buildChildEntityToLinkMap($products, $linkField);
        $childToParentMap = $this->buildChildToParentMap($relations, $childEntityToLinkMap);

        return $this->processProducts(
            $products,
            $childToParentMap,
            $feedSpecification,
            $childTypeIdsList,
            $linkField
        );
    }

    /**
     * @param array $products
     * @param array $childToParentMap
     * @param FeedSpecificationInterface $feedSpecification
     * @param array $childTypeIdsList
     * @param string $linkField
     * @return array
     */
    private function processProducts(
        array                      $products,
        array                      $childToParentMap,
        FeedSpecificationInterface $feedSpecification,
        array                      $childTypeIdsList,
        string                     $linkField
    ): array
    {
        $finalProducts = [];
        $ignoredFields = $feedSpecification->getIgnoreFields();

        foreach ($products as &$product) {
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            $childLinkId = (int)$productModel->getData($linkField);
            $parentLinkIds = array_keys($childToParentMap[$childLinkId] ?? []);

            $isChildVisible = $this->isVisibleIndividually($productModel);

            $product = $this->enrichChildData($product, $productModel, $feedSpecification, $ignoredFields);

            if ($isChildVisible) {
                $standalone = $this->buildStandaloneRow($product);

                $finalProducts[] = $standalone;
            }

            if (!$parentLinkIds) {
                continue;
            }

            foreach ($parentLinkIds as $parentId) {
                $parent = $this->parentProductContextManager
                    ->getParentsDataByProductId((int)$parentId);

                if (!$parent) {
                    continue;
                }

                if (!$this->isVisibleIndividually($parent)) {
                    continue;
                }

                if ($this->productExclusion->shouldExclude(
                    $feedSpecification,
                    $productModel,
                    $parent
                )) {
                    continue;
                }

                $childClone = $this->buildParentContextRow(
                    $product,
                    $productModel,
                    $parent,
                    $ignoredFields,
                    $childTypeIdsList,
                    $linkField
                );

                $finalProducts[] = $childClone;
            }
        }
        return array_values($finalProducts);
    }

    /**
     * @param array $product
     * @return array
     */
    private function buildStandaloneRow(array $product): array
    {
        $standalone = $product;
        $standalone[Constant::IS_STANDALONE_PRODUCT_KEY] = true;

        unset(
            $standalone['__parent_id'],
            $standalone['__parent_title'],
            $standalone['parent_status'],
            $standalone['parent_type_id'],
            $standalone['parent_url'],
            $standalone['parent_visibility'],
            $standalone['__parent_image']
        );

        return $standalone;
    }

    /**
     * @param array $product
     * @param Product $productModel
     * @param FeedSpecificationInterface $feedSpecification
     * @param array $ignoredFields
     * @return array
     */
    private function enrichChildData(
        array                      $product,
        Product                    $productModel,
        FeedSpecificationInterface $feedSpecification,
        array                      $ignoredFields
    ): array
    {
        if (!in_array('child_name', $ignoredFields, true)) {
            $product['child_name'] = $productModel->getName();
        }

        if (!in_array('child_sku', $ignoredFields, true)) {
            $product['child_sku'] = $productModel->getSku();
        }

        if (
            $feedSpecification->getIncludeChildPrices() &&
            !in_array('child_final_price', $ignoredFields, true)
        ) {
            $product['child_final_price'] = $productModel
                ->getPriceInfo()
                ->getPrice(FinalPrice::PRICE_CODE)
                ->getMinimalPrice()
                ->getValue();
        }

        return $product;
    }

    /**
     * @param array $product
     * @param Product $productModel
     * @param Product $parent
     * @param array $ignoredFields
     * @param array $childTypeIdsList
     * @param string $linkField
     * @return array
     */
    private function buildParentContextRow(
        array   $product,
        Product $productModel,
        Product $parent,
        array   $ignoredFields,
        array   $childTypeIdsList,
        string  $linkField
    ): array
    {
        $childClone = $product;

        if (!in_array($productModel->getTypeId(), $childTypeIdsList, true)) {
            return $childClone;
        }

        $childClone['__is_belong_to_parent'] = true;

        if (!in_array(['__parent_id', 'parent_id'], $ignoredFields, true)) {
            $childClone['__parent_id'] = $parent->getDataUsingMethod($linkField);
        }

        if (!in_array(['__parent_title', 'parent_title'], $ignoredFields, true)
            && method_exists($parent, 'getName')
        ) {
            $childClone['__parent_title'] = $parent->getName();
        }

        if (!in_array(['__parent_sku', 'parent_sku'], $ignoredFields, true)
            && method_exists($parent, 'getSku')
        ) {
            $childClone['__parent_sku'] = $parent->getSku();
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
        ) {
            $childClone['parent_url'] = $parent->getProductUrl();
        }

        if (!in_array('parent_visibility', $ignoredFields, true)
            && method_exists($parent, 'getVisibility')
        ) {
            $childClone['parent_visibility'] =
                $this->visibility->getVisibilityTextValue(
                    (int)$parent->getVisibility()
                );
        }

        if (!in_array(['parent_image', '__parent_image'], $ignoredFields, true)) {
            $childClone['__parent_image'] = $this->getParentImage($parent);
        }


        return $childClone;
    }

    /**
     * @param Product $parent
     * @return string
     */
    private function getParentImage(Product $parent): string
    {
        $image = $parent->getImage()
            ?: $parent->getSmallImage()
                ?: $parent->getThumbnail();

        if ($image && $image !== 'no_selection') {
            return $parent->getMediaConfig()->getMediaUrl($image);
        }

        return '';
    }

    /**
     * @param array $products
     * @param string $linkField
     * @return array
     */
    private function buildChildEntityToLinkMap(array $products, string $linkField): array
    {
        $map = [];

        foreach ($products as $product) {
            $productModel = $product['product_model'] ?? null;
            if ($productModel) {
                $map[(int)$productModel->getId()] =
                    (int)$productModel->getData($linkField);
            }
        }

        return $map;
    }

    /**
     * @param array $relations
     * @param array $childEntityToLinkMap
     * @return array
     */
    private function buildChildToParentMap(array $relations, array $childEntityToLinkMap): array
    {
        $map = [];

        foreach ($relations as $row) {
            if (!isset($row['product_id'], $row['parent_id'])) {
                continue;
            }

            $childEntityId = (int)$row['product_id'];
            $childLinkId = $childEntityToLinkMap[$childEntityId] ?? null;

            if (!$childLinkId) {
                continue;
            }

            $parentLinkId = (int)$row['parent_id'];
            $map[$childLinkId][$parentLinkId] = true;
        }

        return $map;
    }

    /**
     * @param Product $product
     * @return bool
     */
    private function isVisibleIndividually(Product $product): bool
    {
        return (int)$product->getVisibility() !== \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE;
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
        //do nothing
    }
}
