<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Parent;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentDataContextManager;
use AthosCommerce\Feed\Model\Feed\DataProvider\Option\Visibility;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use AthosCommerce\Feed\Model\Feed\ProductExclusionInterface;
use AthosCommerce\Feed\Model\Feed\ProductTypeIdInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
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
     * @var AthosCommerceLogger
     */
    private $logger;
    /**
     * @var ParentIdSourceFieldEvaluator
     */
    private $parentIdSourceFieldEvaluator;

    /**
     * @param MetadataPool $metadataPool
     * @param RelationsProvider $relationsProvider
     * @param ParentDataContextManager $parentProductContextManager
     * @param Visibility $visibility
     * @param ProductExclusionInterface $productExclusion
     * @param ProductTypeIdInterface $productTypeId
     * @param AthosCommerceLogger $logger
     * @param ParentIdSourceFieldEvaluator $parentIdSourceFieldEvaluator
     */
    public function __construct(
        MetadataPool                 $metadataPool,
        RelationsProvider            $relationsProvider,
        ParentDataContextManager     $parentProductContextManager,
        Visibility                   $visibility,
        ProductExclusionInterface    $productExclusion,
        ProductTypeIdInterface       $productTypeId,
        AthosCommerceLogger          $logger,
        ParentIdSourceFieldEvaluator $parentIdSourceFieldEvaluator
    )
    {
        $this->metadataPool = $metadataPool;
        $this->relationsProvider = $relationsProvider;
        $this->parentProductContextManager = $parentProductContextManager;
        $this->visibility = $visibility;
        $this->productExclusion = $productExclusion;
        $this->productTypeId = $productTypeId;
        $this->logger = $logger;
        $this->parentIdSourceFieldEvaluator = $parentIdSourceFieldEvaluator;
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
        $parentIdIdentifier = $feedSpecification->getParentIdSourceFieldName() ?: $linkField;

        $childEntityToLink = [];
        foreach ($products as $product) {
            $productModel = $product['product_model'] ?? null;
            if ($productModel) {
                $childEntityToLink[(int)$productModel->getId()] = (int)$productModel->getData($linkField);
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

            $isChildVisible = $this->isVisibleIndividually($productModel);

            $product = $this->enrichChildData(
                $product,
                $productModel,
                $feedSpecification,
                $ignoredFields
            );

            if ($isChildVisible) {
                $finalProducts[] = $this->buildStandaloneRow($product);
            }

            if (!$parentLinkIds) {
                if (!$isChildVisible) {
                    $finalProducts[] = $product;
                }
                continue;
            }

            foreach ($parentLinkIds as $parentId) {
                $parent = $this->parentProductContextManager->getParentsDataByProductId((int)$parentId);

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

                $childClone = $this->buildParentContextRow(
                    $product,
                    $productModel,
                    $parent,
                    $ignoredFields,
                    $childTypeIds,
                    $parentIdIdentifier
                );

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
     * @param Product $parent
     * @return string
     */
    private function getParentImage(Product $parent): string
    {
        $image = $parent->getImage() ?: $parent->getSmallImage() ?: $parent->getThumbnail();

        if ($image && $image !== 'no_selection') {
            return $parent->getMediaConfig()->getMediaUrl($image);
        }

        return '';
    }

    /**
     * @param array $product
     * @param Product $productModel
     * @param Product $parent
     * @param array $ignoredFields
     * @param array $childTypeIds
     * @param string $parentIdIdentifier
     * @return array
     */
    private function buildParentContextRow(
        array   $product,
        Product $productModel,
        Product $parent,
        array   $ignoredFields,
        array   $childTypeIds,
        string  $parentIdIdentifier
    ): array
    {
        $childClone = $product;

        if (!in_array($productModel->getTypeId(), $childTypeIds, true)) {
            return $childClone;
        }

        $childClone['__is_belong_to_parent'] = true;

        if (
            !in_array('__parent_id', $ignoredFields, true)
            && !in_array('parent_id', $ignoredFields, true)
        ) {
            $parentIdentifierValue = $this->parentIdSourceFieldEvaluator->execute($parent, $parentIdIdentifier);

            if ($parentIdentifierValue !== null) {
                $childClone['__parent_id'] = $parentIdentifierValue;
            }
        }

        if (
            !in_array('__parent_title', $ignoredFields, true)
            && !in_array('parent_title', $ignoredFields, true)
            && method_exists($parent, 'getName')
            && $parent->getName()
        ) {
            $childClone['__parent_title'] = $parent->getName();
        }

        if (
            !in_array('__parent_sku', $ignoredFields, true)
            && !in_array('parent_sku', $ignoredFields, true)
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
            && $parent->getProductUrl()
        ) {
            $childClone['parent_url'] = $parent->getProductUrl();
        }

        if (!in_array('parent_visibility', $ignoredFields, true)
            && method_exists($parent, 'getVisibility')
        ) {
            $childClone['parent_visibility'] = $this->visibility->getVisibilityTextValue(
                (int)$parent->getVisibility()
            );
        }

        if (
            !in_array('parent_image', $ignoredFields, true)
            && !in_array('__parent_image', $ignoredFields, true)
        ) {
            $childClone['__parent_image'] = $this->getParentImage($parent);
        }

        return $childClone;
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

        return $product;
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
            $standalone['__is_belong_to_parent'],
            $standalone['__parent_id'],
            $standalone['__parent_title'],
            $standalone['__parent_sku'],
            $standalone['parent_status'],
            $standalone['parent_type_id'],
            $standalone['parent_url'],
            $standalone['parent_visibility'],
            $standalone['__parent_image']
        );

        return $standalone;
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
