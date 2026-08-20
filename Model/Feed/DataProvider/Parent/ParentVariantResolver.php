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

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentRelationsContext;
use Magento\Catalog\Model\Product;

class ParentVariantResolver
{
    /**
     * @var ParentRelationsContext
     */
    private $parentRelationsContext;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param ParentRelationsContext $parentRelationsContext
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        ParentRelationsContext $parentRelationsContext,
        AthosCommerceLogger $logger
    ) {
        $this->parentRelationsContext = $parentRelationsContext;
        $this->logger = $logger;
    }

    /**
     * Backward-compatible helper that returns the first available parent.
     *
     * @param Product $productModel
     * @return Product|null
     */
    public function getParentProduct(Product $productModel): ?Product
    {
        return $this->parentRelationsContext->getParentsByChildId((int)$productModel->getId());
    }

    /**
     * @param Product $productModel
     * @return Product[]
     */
    public function getParentProducts(Product $productModel): array
    {
        $parents = $this->parentRelationsContext->getAllParentsByChildId((int)$productModel->getId());

        return array_values(array_filter($parents, static function ($parent): bool {
            return $parent instanceof Product;
        }));
    }

    /**
     * Resolve the correct parent product for the current export row.
     *
     * This is required when one child product belongs to multiple parents
     * (for example multiple grouped products) and the correct parent must
     * be selected from row context.
     *
     * Resolution order:
     * 1. explicit parent ID fields on the row
     * 2. explicit parent SKU / resolver-related row values
     * 3. first grouped parent matching row context
     * 4. fallback to the first available parent
     *
     * @param array $row
     * @param Product $productModel
     * @return Product|null
     */
    public function resolveParentProductForRow(array $row, Product $productModel): ?Product
    {
        $parents = $this->getParentProducts($productModel);

        if (empty($parents)) {
            return null;
        }

        $resolvedRowParentId = $this->extractResolvedParentId($row);
        if ($resolvedRowParentId !== null) {
            foreach ($parents as $parent) {
                if ((int)$parent->getId() === $resolvedRowParentId) {
                    return $parent;
                }
            }
        }

        $resolvedRowParentSku = $this->extractResolvedParentSku($row);
        if ($resolvedRowParentSku !== null) {
            foreach ($parents as $parent) {
                if ((string)$parent->getSku() === $resolvedRowParentSku) {
                    return $parent;
                }
            }
        }

        foreach ($parents as $parent) {
            if (
                $parent->getTypeId() === Constant::GROUPED_TYPE
                && $this->isChildAssignedToParentRow($row, $parent)
            ) {
                return $parent;
            }
        }

        return $parents[0];
    }

    /**
     * @param Product $parentProduct
     * @return Product[]
     */
    public function getChildProducts(Product $parentProduct): array
    {
        if ($parentProduct->getTypeId() === Constant::CONFIGURABLE_TYPE) {
            return $parentProduct->getTypeInstance()->getUsedProducts($parentProduct);
        }

        if ($parentProduct->getTypeId() === Constant::GROUPED_TYPE) {
            return $parentProduct->getTypeInstance()->getAssociatedProducts($parentProduct);
        }

        return [];
    }

    /**
     * @param Product $parentProduct
     * @param Product $childProduct
     * @return array
     */
    public function getVariantOptions(Product $parentProduct, Product $childProduct): array
    {
        if ($parentProduct->getTypeId() !== Constant::CONFIGURABLE_TYPE) {
            return [];
        }

        $variantOptions = [];
        $configurableAttributes = $parentProduct->getTypeInstance()->getConfigurableAttributes($parentProduct);

        foreach ($configurableAttributes as $attribute) {
            $attr = $attribute->getProductAttribute();

            if (!$attr) {
                continue;
            }

            $attrCode = $attr->getAttributeCode();
            $value = $childProduct->getAttributeText($attrCode);

            if ($value) {
                $variantOptions[$attrCode] = ['value' => $value];
            }
        }

        return $variantOptions;
    }

    /**
     * Try to resolve parent ID from row data.
     *
     * Only uses the stable resolver constants — generic keys like parent_id / __parent_id
     * are intentionally excluded because they reflect a configurable source field and
     * cannot be relied upon to always contain the internal entity ID.
     *
     * @param array $row
     * @return int|null
     */
    private function extractResolvedParentId(array $row): ?int
    {
        $value = $row[Constant::RESOLVED_PARENT_ID_KEY] ?? null;

        if ($this->isNumericScalar($value)) {
            return (int)$value;
        }

        return null;
    }

    /**
     * Try to resolve parent SKU from row data.
     *
     * Only uses the stable resolver constants — generic keys like parent_sku / __parent_sku
     * are intentionally excluded because they reflect a configurable source field.
     *
     * @param array $row
     * @return string|null
     */
    private function extractResolvedParentSku(array $row): ?string
    {
        $value = $row[Constant::RESOLVED_PARENT_SKU_KEY] ?? null;

        if (is_scalar($value)) {
            $value = trim((string)$value);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * Determine whether the row was built for the given parent.
     *
     * Only reads the stable resolver constants written by ConfigurableDataProvider
     * and GroupedDataProvider. Generic fields like parent_sku / __parent_sku are
     * intentionally excluded because they reflect a configurable source field.
     *
     * @param array $row
     * @param Product $parentProduct
     * @return bool
     */
    private function isChildAssignedToParentRow(array $row, Product $parentProduct): bool
    {
        $parentId  = (string)$parentProduct->getId();
        $parentSku = (string)$parentProduct->getSku();

        $resolvedId  = $row[Constant::RESOLVED_PARENT_ID_KEY]  ?? null;
        $resolvedSku = $row[Constant::RESOLVED_PARENT_SKU_KEY] ?? null;

        if ($resolvedId !== null && $this->isNumericScalar($resolvedId)) {
            return (string)(int)$resolvedId === $parentId;
        }

        if ($resolvedSku !== null && is_scalar($resolvedSku)) {
            return trim((string)$resolvedSku) === $parentSku;
        }

        return false;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private function isNumericScalar($value): bool
    {
        if (!is_scalar($value)) {
            return false;
        }

        return ctype_digit((string)$value);
    }
}
