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
     * @param ParentVariantResolver $parentVariantResolver
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        ParentVariantResolver $parentVariantResolver,
        AthosCommerceLogger   $logger
    )
    {
        $this->parentVariantResolver = $parentVariantResolver;
        $this->logger = $logger;
    }

    public function getData(
        array                      $products,
        FeedSpecificationInterface $feedSpecification
    ): array
    {
        $ignoredFields = $feedSpecification->getIgnoreFields();
        $groupBySourceFieldName = $feedSpecification->getGroupBySourceFieldName();

        if (in_array('__group_id', $ignoredFields, true)) {
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
                $product['__group_id'] = (string)$productModel->getId();
                continue;
            }

            if ($parentProduct->getTypeId() === 'grouped') {
                $product['__group_id'] = (string)$parentProduct->getId();
                continue;
            }

            $variantOptions = $this->parentVariantResolver->getVariantOptions($parentProduct, $productModel);

            $product['__group_id'] = $this->buildGroupId(
                $parentProduct,
                $variantOptions,
                $groupBySourceFieldName
            );

            $this->logger->debug(
                sprintf(
                    'Assigned group ID "%s" to product ID %d based on parent product ID %d and group by attribute "%s".',
                    $product['__group_id'],
                    $productModel->getId(),
                    $parentProduct->getId(),
                    $groupBySourceFieldName ?? 'N/A'
                )
            );
        }

        return $products;
    }

    private function buildGroupId(
        Product $parentProduct,
        array   $variantOptions,
        ?string $groupByAttribute = null
    ): string
    {
        $parentId = (string)$parentProduct->getId();

        if (!$groupByAttribute) {
            return $parentId;
        }

        $groupIdValue = $variantOptions[$groupByAttribute]['value'] ?? '';

        if ($groupIdValue === '' || $groupIdValue === null) {
            return $parentId;
        }

        return "{$parentId}::{$groupIdValue}";
    }

    public function reset(): void
    {
    }

    public function resetAfterFetchItems(): void
    {
    }
}
