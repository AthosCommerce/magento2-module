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

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Context;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Collection as ParentProductCollection;
use Magento\Catalog\Api\Data\ProductInterface;

class ParentDataContextManager
{
    /**
     * @var array
     */
    private $productData = [];
    /**
     * @var ParentProductCollection
     */
    private $parentProductCollection;
    /** @var int[] */
    private $loadedParentIds = [];

    /**
     * @param Collection $parentProductCollection
     */
    public function __construct(
        ParentProductCollection $parentProductCollection,
    ) {
        $this->parentProductCollection = $parentProductCollection;
    }

    /**
     * @param array $allParentIds
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     */
    public function execute(
        array $allParentIds,
        FeedSpecificationInterface $feedSpecification
    ): array {
        if (empty(array_diff(array_unique($allParentIds), $this->loadedParentIds))) {
            return $this->productData;
        }
        $parentCollection = $this->parentProductCollection->execute(
            array_values(array_unique($allParentIds)),
            $feedSpecification
        );

        /** @var ProductInterface $parentProduct */
        foreach ($parentCollection as $parentProduct) {
            $parentId = (int)$parentProduct->getId();
            $this->productData[$parentId] = $parentProduct;
            $this->loadedParentIds[] = $parentId;
        }

        return $this->productData;
    }

    /**
     * @param int $productId
     *
     * @return ProductInterface[]|null
     */
    public function getParentsDataByProductId(int $productId)
    {
        return isset($this->productData[$productId])
            ? $this->productData[$productId]
            : null;
    }

    /**
     * @return void
     */
    public function resetContext(): void
    {
        $this->loadedParentIds = [];
        $this->productData = [];
    }
}
