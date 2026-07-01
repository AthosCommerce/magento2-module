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

        $parentIdIdentifier = $feedSpecification->getParentIdSourceFieldName();
        if (empty($parentIdIdentifier)) {
            $parentIdIdentifier = $linkField;
        }

        foreach ($products as $product) {
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            $childLinkId = (int)$productModel->getData($linkField);
            $parentLinkIds = array_keys($childToParentMap[$childLinkId] ?? []);

            $isChildVisible = $this->isVisibleIndividually($productModel);
            $this->logger->debug(
                sprintf('[ConfigurableDataProvider] Child(%s)', $childLinkId),
                [
                    'childLinkId' => $childLinkId,
                    'parentLinkIds' => $parentLinkIds,
                    'isChildVisible' => $isChildVisible,
                ]
            );

            $product = $this->enrichChildData($product, $productModel, $feedSpecification, $ignoredFields);

            if ($isChildVisible) {
                $standalone = $this->buildStandaloneRow($product);
                $finalProducts[] = $standalone;
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
                    $parentIdIdentifier
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
     * @param string $parentIdIdentifier
     * @return array
     */
    private function buildParentContextRow(
        array   $product,
        Product $productModel,
        Product $parent,
        array   $ignoredFields,
        array   $childTypeIdsList,
        string  $parentIdIdentifier
    ): array
    {
        $childClone = $product;

        if (!in_array($productModel->getTypeId(), $childTypeIdsList, true)) {
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
        ) {
            $childClone[Constant::PARENT_URL] = $parent->getProductUrl();
        }

        if (!in_array(Constant::PARENT_VISIBILITY, $ignoredFields, true)
            && method_exists($parent, 'getVisibility')
        ) {
            $childClone[Constant::PARENT_VISIBILITY] = $this->visibility->getVisibilityTextValue((int)$parent->getVisibility());
        }

        if (!in_array(Constant::PARENT_IMAGE, $ignoredFields, true)) {
            $childClone[Constant::PARENT_IMAGE] = $this->getParentImage($parent);
        }

        $childClone[Constant::RESOLVED_PARENT_ID_KEY] = (int)$parent->getId();
        $childClone[Constant::RESOLVED_PARENT_SKU_KEY] = (string)$parent->getSku();
        $childClone[Constant::RESOLVED_PARENT_TYPE_KEY] = (string)$parent->getTypeId();

        $childClone[Constant::RESOLVED_PARENT_ROW_SOURCE_KEY] = 'configurable';

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
                $map[(int)$productModel->getId()] = (int)$productModel->getData($linkField);
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
