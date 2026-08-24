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
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class ProductNextActionProvider
{
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @param ProductCollectionFactory $productCollectionFactory
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory
    )
    {
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @param ProductInterface $product
     *
     * @return string
     */
    public function getNextActionByProduct(ProductInterface $product): string
    {
        return $this->resolveNextAction(
            (int)$product->getStatus(),
            (int)$product->getVisibility()
        );
    }

    /**
     * @param array $productIds
     *
     * @return array<int, string>
     */
    public function getNextActionsByProductIds(array $productIds): array
    {
        $productIds = $this->normalizeProductIds($productIds);
        if ($productIds === []) {
            return [];
        }

        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId(0);
        $collection->addAttributeToSelect(['status', 'visibility']);
        $collection->addFieldToFilter('entity_id', ['in' => $productIds]);

        $nextActions = [];
        foreach ($collection as $product) {
            $nextActions[(int)$product->getId()] = $this->getNextActionByProduct($product);
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
