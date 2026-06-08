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
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentIdSourceFieldEvaluator;
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

            if (!$parentLinkIds) {
                $finalProducts[] = $product;
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

                $childClone = $product;

                if (in_array($productModel->getTypeId(), $childTypeIds, true)) {
                    if (!in_array(['__parent_id', 'parent_id'], $ignoredFields, true)) {
                        $parentIdIdentifier = $feedSpecification->getParentIdSourceFieldName() ?: $this->getLinkField();

                        $parentIdentifierValue = $this->parentIdSourceFieldEvaluator->execute($parent, $parentIdIdentifier);

                        if ($parentIdentifierValue !== null) {
                            $childClone['__parent_id'] = $parentIdentifierValue;
                        }
                    }

                    if (!in_array(['__parent_title', 'parent_title'], $ignoredFields, true)
                        && method_exists($parent, 'getName')
                        && $parent->getName()
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
                        && $parent->getProductUrl()
                    ) {
                        $childClone['parent_url'] = $parent->getProductUrl();
                    }

                    if (!in_array('parent_visibility', $ignoredFields, true)
                        && method_exists($parent, 'getVisibility')
                        && $parent->getVisibility()
                    ) {
                        $childClone['parent_visibility'] = $this->visibility->getVisibilityTextValue(
                            (int)$parent->getVisibility()
                        );
                    }

                    $parentImage = '';
                    if (!in_array(['parent_image', '__parent_image'], $ignoredFields, true)) {
                        $image = $parent->getImage() ?: $parent->getSmallImage() ?: $parent->getThumbnail();
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
