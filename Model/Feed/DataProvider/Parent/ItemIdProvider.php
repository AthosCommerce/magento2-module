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
use AthosCommerce\Feed\Model\Feed\ProductTypeIdInterface;
use Magento\Catalog\Model\Product;

class ItemIdProvider
{
    /**
     * @var ProductTypeIdInterface
     */
    protected $productTypeId;
    /**
     * @var ParentVariantResolver
     */
    private $parentVariantResolver;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param ParentVariantResolver $parentVariantResolver
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        ProductTypeIdInterface $productTypeId,
        ParentVariantResolver  $parentVariantResolver,
        AthosCommerceLogger    $logger
    )
    {
        $this->productTypeId = $productTypeId;
        $this->parentVariantResolver = $parentVariantResolver;
        $this->logger = $logger;
    }

    /**
     * @param array $product
     * @param Product $productModel
     * @return string
     */
    public function execute(array $product, Product $productModel): string
    {
        $productEntityId = (string)$productModel->getId();

        $isBelongToParent = (bool)($product[Constant::IS_BELONG_TO_PARENT_KEY] ?? false);

        $parentProduct = $this->parentVariantResolver->resolveParentProductForRow($product, $productModel);

        if (!$parentProduct instanceof Product) {
            return $productEntityId;
        }

        if (in_array(
            $parentProduct->getTypeId(),
            $this->productTypeId->getParentTypeIdsList(),
            true
        )) {
            $productEntityId = $isBelongToParent
                ? (string)$parentProduct->getId() . '_' . $productEntityId
                : $productEntityId;
        }
        return $productEntityId;
    }
}
