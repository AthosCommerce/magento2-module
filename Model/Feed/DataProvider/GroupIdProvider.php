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
        AthosCommerceLogger $logger
    ) {
        $this->parentVariantResolver = $parentVariantResolver;
        $this->logger = $logger;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     */
    public function getData(
        array $products,
        FeedSpecificationInterface $feedSpecification
    ): array {
        $ignoredFields = $feedSpecification->getIgnoreFields();
        $groupBySourceFieldName = $feedSpecification->getGroupBySourceFieldName();

        if (in_array('__group_id', $ignoredFields, true)) {
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
            $parentProduct = $this->parentVariantResolver->resolveParentProductForRow($product, $productModel);

            if (!$parentProduct instanceof Product) {
                $product['__group_id'] = (string)$productModel->getId();
                continue;
            }

            if ($parentProduct->getTypeId() === Constant::GROUPED_TYPE) {
                $product['__group_id'] = $isBelongToParent
                    ? (string)$parentProduct->getId()
                    : (string)$productModel->getId();

                $this->logger->debug(
                    sprintf(
                        '[GroupId]Assigned groupID:[%s] to PID:[%d] using parent PID:[%d] (isBelongToParent=%s).',
                        $product['__group_id'],
                        (int)$productModel->getId(),
                        (int)$parentProduct->getId(),
                        $isBelongToParent ? 'true' : 'false'
                    )
                );

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
                    '[GroupId]Assigned groupID:[%s] to PID:[%d] based on ParentPID [%d] and groupByAttribute [%s].',
                    $product['__group_id'],
                    (int)$productModel->getId(),
                    (int)$parentProduct->getId(),
                    $groupBySourceFieldName !== null ? $groupBySourceFieldName : 'N/A'
                )
            );
        }
        unset($product);

        return $products;
    }

    /**
     * @param Product $parentProduct
     * @param array $variantOptions
     * @param string|null $groupByAttribute
     * @return string
     */
    private function buildGroupId(
        Product $parentProduct,
        array $variantOptions,
        ?string $groupByAttribute = null
    ): string {
        $parentId = (string)$parentProduct->getId();

        if (!$groupByAttribute) {
            return $parentId;
        }

        $groupIdValue = $variantOptions[$groupByAttribute]['value'] ?? '';

        if ($groupIdValue === '' || $groupIdValue === null) {
            return $parentId;
        }

        return $parentId . '::' . $groupIdValue;
    }

    public function reset(): void
    {
    }

    public function resetAfterFetchItems(): void
    {
    }
}
