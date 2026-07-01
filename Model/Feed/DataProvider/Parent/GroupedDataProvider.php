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
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentRelationsContext;
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
        $this->logger->info("[GroupedDataProvider] Started");
        $childTypeIds = $this->productTypeId->getChildTypeIdsList();
        $childEntityIds = $this->getChildIds($products, $childTypeIds);
        if (!$childEntityIds) {
            $this->logger->debug(
                '[GroupedDataProvider] No child entity ids found'
            );
            return $products;
        }

        $relations = $this->relationsProvider->getGroupRelationIds($childEntityIds);
        if (!$relations) {
            $this->logger->debug(
                '[GroupedDataProvider] No relations found for child entity ids',
                [
                    'childEntityIds' => $childEntityIds,
                ]
            );
            return $products;
        }

        $ignoredFields = $feedSpecification->getIgnoreFields();
        $linkField = $this->getLinkField();

        $parentIdIdentifier = $feedSpecification->getParentIdSourceFieldName();
        if (empty($parentIdIdentifier)) {
            $parentIdIdentifier = $linkField;
        }

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

            if (($product[Constant::RESOLVED_PARENT_ROW_SOURCE_KEY] ?? null) === 'configurable') {
                $finalProducts[] = $product;
                continue;
            }

            $childLinkId = (int)$productModel->getData($linkField);
            $parentLinkIds = array_keys($childToParent[$childLinkId] ?? []);
            $isChildVisible = $this->isVisibleIndividually($productModel);

            $this->logger->debug(
                sprintf('[GroupedDataProvider] Child(%s)', $childLinkId),
                [
                    'childLinkId' => $childLinkId,
                    'parentLinkIds' => $parentLinkIds,
                    'isChildVisible' => $isChildVisible,
                ]
            );

            $product = $this->enrichChildData(
                $product,
                $productModel,
                $feedSpecification,
                $ignoredFields
            );

            if ($isChildVisible) {
                $finalProducts[] = $this->buildStandaloneRow($product);
                $this->logger->debug(
                    sprintf('[GroupedDataProvider] Standalone row (%s) added for child product.', $childLinkId)
                );
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
                    $this->logger->debug(
                        sprintf('[GroupedDataProvider] ParentID for (%s) not found in context.', $parentId)
                    );
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
        $this->logger->info("[GroupedDataProvider] Finished");

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
            if ($productModel && in_array($productModel->getTypeId(), $childTypeIdsList, true)
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

        $this->logger->debug(
            '[GroupedDataProvider] Building parent-child row',
            [
                'id' => $productModel->getId(),
                'typeId' => $productModel->getTypeId(),
                'parentId' => $parent->getId(),
                'parentSku' => $parent->getSku(),
                'childTypeIds' => $childTypeIds,
            ]
        );
        if (!in_array($productModel->getTypeId(), $childTypeIds, true)) {
            return $childClone;
        }

        $childClone[Constant::IS_BELONG_TO_PARENT_KEY] = true;
        $childClone[Constant::IS_STANDALONE_PRODUCT_KEY] = false;

        if (!in_array(Constant::PARENT_ID, $ignoredFields, true)) {
            $parentIdentifierValue = $this->parentIdSourceFieldEvaluator->execute($parent, $parentIdIdentifier);

            if ($parentIdentifierValue !== null) {
                $childClone[Constant::PARENT_ID] = $parentIdentifierValue;
            }
        }

        if (!in_array(Constant::PARENT_TITLE, $ignoredFields, true)) {
            $childClone[Constant::PARENT_TITLE] = $parent->getDataUsingMethod('name');
        }

        if (!in_array(Constant::PARENT_SKU, $ignoredFields, true)) {
            $childClone[Constant::PARENT_SKU] = $parent->getDataUsingMethod('sku');
        }

        if (!in_array(Constant::PARENT_STATUS, $ignoredFields, true)) {
            $childClone[Constant::PARENT_STATUS] = $parent->getDataUsingMethod('status')
                ? __('Enabled')->getText()
                : __('Disabled')->getText();
        }

        if (!in_array(Constant::PARENT_TYPE, $ignoredFields, true)) {
            $childClone[Constant::PARENT_TYPE] = $parent->getDataUsingMethod('type_id');
        }

        if (!in_array(Constant::PARENT_URL, $ignoredFields, true)
            && method_exists($parent, 'getProductUrl')
            && $parent->getProductUrl()
        ) {
            $childClone[Constant::PARENT_URL] = $parent->getProductUrl();
        }

        if (!in_array(Constant::PARENT_VISIBILITY, $ignoredFields, true)
            && method_exists($parent, 'getVisibility')
        ) {
            $childClone[Constant::PARENT_VISIBILITY] = $this->visibility->getVisibilityTextValue(
                (int)$parent->getVisibility()
            );
        }

        if (!in_array(Constant::PARENT_IMAGE, $ignoredFields, true)) {
            $childClone[Constant::PARENT_IMAGE] = $this->getParentImage($parent);
        }

        $childClone[Constant::RESOLVED_PARENT_ID_KEY] = (int)$parent->getId();
        $childClone[Constant::RESOLVED_PARENT_SKU_KEY] = (string)$parent->getSku();
        $childClone[Constant::RESOLVED_PARENT_TYPE_KEY] = (string)$parent->getTypeId();
        $childClone[Constant::RESOLVED_PARENT_ROW_SOURCE_KEY] = 'grouped';

        $this->logger->debug('[GroupedDataProvider] Parent Child record completed.');

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
        $standalone[Constant::IS_BELONG_TO_PARENT_KEY] = false;

        unset(
            $standalone[Constant::PARENT_ID],
            $standalone[Constant::PARENT_TITLE],
            $standalone[Constant::PARENT_SKU],
            $standalone[Constant::PARENT_IMAGE],
            $standalone[Constant::PARENT_STATUS],
            $standalone[Constant::PARENT_TYPE],
            $standalone[Constant::PARENT_URL],
            $standalone[Constant::PARENT_VISIBILITY]
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
