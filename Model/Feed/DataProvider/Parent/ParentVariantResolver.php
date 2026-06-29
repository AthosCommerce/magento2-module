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
     * @param array $row
     * @return int|null
     */
    private function extractResolvedParentId(array $row): ?int
    {
        $candidateKeys = [
            'parent_id',
            '__parent_id',
            'parent_product_id',
            '__parent_product_id',
            '__resolver_parent_id',
        ];

        foreach ($candidateKeys as $key) {
            if (!array_key_exists($key, $row)) {
                continue;
            }

            $value = $row[$key];

            if ($this->isNumericScalar($value)) {
                return (int)$value;
            }
        }

        return null;
    }

    /**
     * Try to resolve parent SKU from row data.
     *
     * @param array $row
     * @return string|null
     */
    private function extractResolvedParentSku(array $row): ?string
    {
        $candidateKeys = [
            'parent_sku',
            '__parent_sku',
            'parent_product_sku',
            '__parent_product_sku',
            '__resolver_parent_sku',
        ];

        foreach ($candidateKeys as $key) {
            if (!array_key_exists($key, $row)) {
                continue;
            }

            $value = $row[$key];

            if (is_scalar($value)) {
                $value = trim((string)$value);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Determine whether the row appears to belong to the given parent.
     *
     * @param array $row
     * @param Product $parentProduct
     * @return bool
     */
    private function isChildAssignedToParentRow(array $row, Product $parentProduct): bool
    {
        $parentId = (string)$parentProduct->getId();
        $parentSku = (string)$parentProduct->getSku();

        $candidateValues = [
            isset($row['sku']) ? $row['sku'] : null,
            isset($row['parent_sku']) ? $row['parent_sku'] : null,
            isset($row['__parent_sku']) ? $row['__parent_sku'] : null,
            isset($row['parent_product_sku']) ? $row['parent_product_sku'] : null,
            isset($row['__parent_product_sku']) ? $row['__parent_product_sku'] : null,
            isset($row['grouped_products']) ? $row['grouped_products'] : null,
            isset($row['__resolver']) ? $row['__resolver'] : null,
            isset($row['__resolver_parent_sku']) ? $row['__resolver_parent_sku'] : null,
            isset($row['__resolver_parent_id']) ? $row['__resolver_parent_id'] : null,
        ];

        foreach ($candidateValues as $value) {
            if ($this->matchesParentIdentity($value, $parentId, $parentSku)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $value
     * @param string $parentId
     * @param string $parentSku
     * @return bool
     */
    private function matchesParentIdentity($value, string $parentId, string $parentSku): bool
    {
        if (is_scalar($value)) {
            $scalarValue = trim((string)$value);

            return $scalarValue === $parentId || $scalarValue === $parentSku;
        }

        if (is_array($value)) {
            foreach ($value as $nestedValue) {
                if (!$this->matchesParentIdentity($nestedValue, $parentId, $parentSku)) {
                    continue;
                }

                return true;
            }
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
