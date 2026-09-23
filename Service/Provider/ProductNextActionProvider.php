<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Service\Provider;

use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Service\GroupedParentIdResolver;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class ProductNextActionProvider
{
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var Configurable
     */
    private $configurableType;

    /**
     * @var GroupedParentIdResolver
     */
    private $groupedParentIdResolver;

    /**
     * @param ProductCollectionFactory $productCollectionFactory
     * @param Configurable $configurableType
     * @param GroupedParentIdResolver $groupedParentIdResolver
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        Configurable $configurableType,
        GroupedParentIdResolver $groupedParentIdResolver
    )
    {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->configurableType = $configurableType;
        $this->groupedParentIdResolver = $groupedParentIdResolver;
    }

    /**
     * @param ProductInterface $product
     * @param int|null $storeId
     *
     * @return string
     */
    public function getNextActionByProduct(ProductInterface $product, ?int $storeId = null): string
    {
        if ($storeId !== null && $this->hasVisibleEnabledParent($product, $storeId)) {
            return Actions::UPSERT;
        }

        return $this->resolveNextAction(
            (int)$product->getStatus(),
            (int)$product->getVisibility()
        );
    }

    /**
     * @param array $productIds
     * @param int|null $storeId
     *
     * @return array<int, string>
     */
    public function getNextActionsByProductIds(array $productIds, ?int $storeId = null): array
    {
        $productIds = $this->normalizeProductIds($productIds);
        if ($productIds === []) {
            return [];
        }

        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId($storeId ?? 0);
        $collection->addAttributeToSelect(['status', 'visibility']);
        $collection->addFieldToFilter('entity_id', ['in' => $productIds]);

        $nextActions = [];
        foreach ($collection as $product) {
            $nextActions[(int)$product->getId()] = $this->getNextActionByProduct($product, $storeId);
        }

        return $nextActions;
    }

    /**
     * @param int $status
     * @param int $visibility
     *
     * @return string
     */
    private function resolveNextAction(int $status, int $visibility): string
    {
        return ($status !== Status::STATUS_ENABLED || $visibility === Visibility::VISIBILITY_NOT_VISIBLE)
            ? Actions::DELETE
            : Actions::UPSERT;
    }

    /**
     * @param ProductInterface $product
     * @param int $storeId
     * @return bool
     */
    private function hasVisibleEnabledParent(ProductInterface $product, int $storeId): bool
    {
        if ((int)$product->getStatus() !== Status::STATUS_ENABLED
            || (int)$product->getVisibility() !== Visibility::VISIBILITY_NOT_VISIBLE
        ) {
            return false;
        }

        $groupedParentIds = $this->groupedParentIdResolver->getParentIdsByChildId((int)$product->getId());
        $parentIds = array_merge(
            $this->configurableType->getParentIdsByChild((int)$product->getId()),
            $groupedParentIds
        );
        $parentIds = array_values(array_unique(array_map('intval', $parentIds)));
        if ($parentIds === []) {
            return false;
        }

        $parentCollection = $this->productCollectionFactory->create();
        $parentCollection->setStoreId($storeId);
        $parentCollection->addAttributeToSelect(['status', 'visibility']);
        $parentCollection->addFieldToFilter('entity_id', ['in' => $parentIds]);

        foreach ($parentCollection as $parentProduct) {
            if ((int)$parentProduct->getStatus() === Status::STATUS_ENABLED
                && (int)$parentProduct->getVisibility() !== Visibility::VISIBILITY_NOT_VISIBLE
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $productIds
     *
     * @return array<int, int>
     */
    private function normalizeProductIds(array $productIds): array
    {
        return array_values(
            array_unique(
                array_filter(
                    array_map('intval', $productIds)
                )
            )
        );
    }
}
