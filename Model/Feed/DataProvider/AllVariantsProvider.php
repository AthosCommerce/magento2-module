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
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\LocalizedException;

class AllVariantsProvider implements DataProviderInterface
{
    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @var ParentVariantResolver
     */
    private $parentVariantResolver;

    public function __construct(
        AthosCommerceLogger    $logger,
        ParentVariantResolver  $parentVariantResolver,
        StockRegistryInterface $stockRegistry
    )
    {
        $this->logger = $logger;
        $this->parentVariantResolver = $parentVariantResolver;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws LocalizedException
     */
    public function getData(
        array                      $products,
        FeedSpecificationInterface $feedSpecification
    ): array
    {
        $ignoredFields = $feedSpecification->getIgnoreFields();

        if (
            in_array('__all_variants', $ignoredFields, true)
            || !$feedSpecification->getIncludeAllVariants()
        ) {
            return $products;
        }

        foreach ($products as &$product) {
            /** @var Product|null $productModel */
            $productModel = $product['product_model'] ?? null;

            if (!$productModel || !in_array($productModel->getTypeId(), ['simple', 'virtual'], true)) {
                continue;
            }

            $parentProduct = $this->parentVariantResolver->getParentProduct($productModel);

            if (!$parentProduct) {
                $product['__all_variants'] = [];
                continue;
            }

            $allVariants = [];
            $childProducts = $this->parentVariantResolver->getChildProducts($parentProduct);

            foreach ($childProducts as $child) {
                $variantOptions = $this->parentVariantResolver->getVariantOptions($parentProduct, $child);

                $allVariants[] = $this->buildVariantRow($child, $variantOptions);
            }

            $product['__all_variants'] = $allVariants;
        }

        return $products;
    }

    /**
     * @param Product $child
     * @param array $variantOptions
     * @return array
     */
    private function buildVariantRow(
        Product $child,
        array   $variantOptions = []
    ): array
    {
        $stockItem = $this->stockRegistry->getStockItem((int)$child->getId());

        return [
            'mappings' => [
                'core' => [
                    'uid' => (int)$child->getId(),
                    'msrp' => $child->getMsrp(),
                    'price' => $child->getPrice(),
                    'final_price' => $child->getFinalPrice(),
                    'url' => $child->getProductUrl(),
                ]
            ],
            'options' => $variantOptions,
            'attributes' => [
                'inventory_quantity' => (int)$stockItem->getQty(),
                'title' => !empty($variantOptions)
                    ? implode(' / ', array_column($variantOptions, 'value'))
                    : (string)$child->getName(),
                'sku' => (string)$child->getSku(),
            ]
        ];
    }

    public function reset(): void
    {
        // No state to reset in this provider
    }

    public function resetAfterFetchItems(): void
    {
        // No state to reset after fetching items in this provider
    }
}
