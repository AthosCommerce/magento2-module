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

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentIdSourceFieldEvaluator;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\Catalog\Model\Product;

class GroupIdProvider implements DataProviderInterface
{
    /**
     * @var ParentVariantResolver
     */
    private $parentVariantResolver;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @var array<string, Product|null>
     */
    private $resolvedParentCache = [];

    /**
     * @var array<string, string>
     */
    private $groupIdCache = [];

    /**
     * @var ParentIdSourceFieldEvaluator
     */
    private $parentIdSourceFieldEvaluator;

    /**
     * Initialize dependencies.
     *
     * @param ParentVariantResolver $parentVariantResolver
     * @param AthosCommerceLogger $logger
     * @param ParentIdSourceFieldEvaluator $parentIdSourceFieldEvaluator
     */
    public function __construct(
        ParentVariantResolver $parentVariantResolver,
        AthosCommerceLogger $logger,
        ParentIdSourceFieldEvaluator $parentIdSourceFieldEvaluator
    ) {
        $this->parentVariantResolver = $parentVariantResolver;
        $this->logger = $logger;
        $this->parentIdSourceFieldEvaluator = $parentIdSourceFieldEvaluator;
    }

    /**
     * Enrich export rows with the group id field.
     *
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     */
    public function getData(
        array                      $products,
        FeedSpecificationInterface $feedSpecification
    ): array {
        $this->logger->info("[GroupIdProvider] Started");
        $ignoredFields = $feedSpecification->getIgnoreFields();
        $groupBySourceFieldName = $feedSpecification->getGroupBySourceFieldName();
        $parentIdSourceFieldName = $feedSpecification->getParentIdSourceFieldName();

        if (in_array(Constant::GROUP_ID, $ignoredFields, true)) {
            return $products;
        }

        foreach ($products as &$product) {
            /** @var Product|null $productModel */
            $productModel = $product['product_model'] ?? null;

            if (!$productModel instanceof Product) {
                continue;
            }

            if (!in_array($productModel->getTypeId(), ['simple', 'virtual'], true)) {
                continue;
            }

            $isBelongToParent = (bool)($product[Constant::IS_BELONG_TO_PARENT_KEY] ?? false);
            $parentProduct = $this->resolveParentProductForRow($product, $productModel);
            $isStandaloneProduct = (bool)($product[Constant::IS_STANDALONE_PRODUCT_KEY] ?? false);
            //$parentProduct = $this->parentVariantResolver->resolveParentProductForRow($product, $productModel);

            if (!$parentProduct instanceof Product) {
                $product[Constant::GROUP_ID] = $this->buildGroupId(
                    $productModel,
                    $productModel,
                    [],
                    $groupBySourceFieldName,
                    $parentIdSourceFieldName
                );
                continue;
            }

            if ($parentProduct->getTypeId() === Constant::GROUPED_TYPE) {
                $groupBaseProduct = $isBelongToParent ? $parentProduct : $productModel;
                $product[Constant::GROUP_ID] = $this->buildGroupId(
                    $groupBaseProduct,
                    $productModel,
                    [],
                    $groupBySourceFieldName,
                    $parentIdSourceFieldName
                );

                $this->logger->debug(
                    sprintf(
                        '[GroupId]Assigned groupID:[%s] to PID:[%d] using parent PID:[%d] (isBelongToParent=%s).',
                        $product[Constant::GROUP_ID],
                        (int)$productModel->getId(),
                        (int)$parentProduct->getId(),
                        $isBelongToParent ? 'true' : 'false'
                    )
                );

                continue;
            }

            if ($parentProduct->getTypeId() === Constant::CONFIGURABLE_TYPE) {

                //To handle the child product having non NVI
                if (!$isBelongToParent) {
                    $product['__group_id'] = (string)$productModel->getId();
                    continue;
                }

                $product['__group_id'] = $this->getConfigurableGroupId(
                    $parentProduct,
                    $productModel,
                    $groupBySourceFieldName
                $variantOptions = $this->parentVariantResolver->getVariantOptions($parentProduct, $productModel);
                $groupBaseProduct = $isBelongToParent && !$isStandaloneProduct
                    ? $parentProduct
                    : $productModel;

                $product[Constant::GROUP_ID] = $this->buildGroupId(
                    $groupBaseProduct,
                    $productModel,
                    $variantOptions,
                    $groupBySourceFieldName,
                    $parentIdSourceFieldName
                );

                $this->logger->debug(
                    sprintf(
                        '[GroupId]Assigned groupID:[%s] to PID:[%d] based on ParentPID [%d] and groupByAttribute [%s].',
                        $product[Constant::GROUP_ID],
                        (int)$productModel->getId(),
                        (int)$parentProduct->getId(),
                        $groupBySourceFieldName !== null ? $groupBySourceFieldName : 'N/A'
                    )
                );
            }
        }
        unset($product);
        $this->logger->info("[GroupIdProvider] Completed $groupBySourceFieldName");
        return $products;
    }

    /**
     * Build the final group identifier for a row.
     *
     * @param Product $baseProduct
     * @param Product $groupValueProduct
     * @param array $variantOptions
     * @param string|null $groupByAttribute
     * @param string|null $parentIdIdentifier
     * @return string
     */
    private function buildGroupId(
        Product $baseProduct,
        Product $groupValueProduct,
        array $variantOptions,
        ?string $groupByAttribute = null,
        ?string $parentIdIdentifier = null
    ): string {
        $parentId = $this->resolveConfiguredGroupBaseId($baseProduct, $parentIdIdentifier);

        if (!$groupByAttribute) {
            return $parentId;
        }

        $groupIdValue = $variantOptions[$groupByAttribute]['value'] ?? null;

        if ($groupIdValue === null || $groupIdValue === '') {
            $groupIdValue = $this->parentIdSourceFieldEvaluator->execute($groupValueProduct, $groupByAttribute);
        }

        if ($groupIdValue === '' || $groupIdValue === null) {
            return $parentId;
        }

        return $parentId . '::' . $groupIdValue;
    }

    /**
     * Resolve the configured base identifier used to build the group id field.
     *
     * @param Product $product
     * @param string|null $parentIdIdentifier
     * @return string
     */
    private function resolveConfiguredGroupBaseId(Product $product, ?string $parentIdIdentifier): string
    {
        $resolvedValue = $this->parentIdSourceFieldEvaluator->execute($product, $parentIdIdentifier);

        if ($resolvedValue !== null && $resolvedValue !== '') {
            return $resolvedValue;
        }

        return (string)$product->getId();
    }

    /**
     * Reset internal state.
     *
     * @return void
     */
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedFunction
    public function reset(): void
    {
        $this->resolvedParentCache = [];
        $this->groupIdCache = [];
    }

    /**
     * Reset state after fetching items.
     *
     * @return void
     */
    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedFunction
    public function resetAfterFetchItems(): void
    {
        $this->reset();
    }

    /**
     * @param array $row
     * @param Product $productModel
     * @return Product|null
     */
    private function resolveParentProductForRow(array $row, Product $productModel): ?Product
    {
        $cacheKey = $this->getParentResolutionCacheKey($row, $productModel);
        if (!array_key_exists($cacheKey, $this->resolvedParentCache)) {
            $this->resolvedParentCache[$cacheKey] = $this->parentVariantResolver->resolveParentProductForRow($row, $productModel);
        }

        return $this->resolvedParentCache[$cacheKey];
    }

    /**
     * @param array $row
     * @param Product $productModel
     * @return string
     */
    private function getParentResolutionCacheKey(array $row, Product $productModel): string
    {
        return implode(':', [
            (string)$productModel->getId(),
            (string)($row[Constant::RESOLVED_PARENT_ID_KEY] ?? ''),
            (string)($row[Constant::RESOLVED_PARENT_SKU_KEY] ?? ''),
            (string)($row[Constant::RESOLVED_PARENT_TYPE_KEY] ?? ''),
            (string)($row[Constant::RESOLVED_PARENT_ROW_SOURCE_KEY] ?? ''),
        ]);
    }

    /**
     * @param Product $parentProduct
     * @param Product $productModel
     * @param string|null $groupBySourceFieldName
     * @return string
     */
    private function getConfigurableGroupId(
        Product $parentProduct,
        Product $productModel,
        ?string $groupBySourceFieldName
    ): string {
        $cacheKey = implode(':', [
            (string)$parentProduct->getId(),
            (string)$productModel->getId(),
            (string)$groupBySourceFieldName,
        ]);

        if (!isset($this->groupIdCache[$cacheKey])) {
            $variantOptions = $this->parentVariantResolver->getVariantOptions($parentProduct, $productModel);
            $this->groupIdCache[$cacheKey] = $this->buildGroupId(
                $parentProduct,
                $variantOptions,
                $groupBySourceFieldName
            );
        }

        return $this->groupIdCache[$cacheKey];
    }
}
