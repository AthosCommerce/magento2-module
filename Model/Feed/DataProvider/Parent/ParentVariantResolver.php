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
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
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
        AthosCommerceLogger    $logger
    )
    {
        $this->parentRelationsContext = $parentRelationsContext;
        $this->logger = $logger;
    }

    /**
     * @param Product $productModel
     * @return Product|null
     */
    public function getParentProduct(Product $productModel): ?Product
    {
        return $this->parentRelationsContext->getParentsByChildId((int)$productModel->getId());
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
}
