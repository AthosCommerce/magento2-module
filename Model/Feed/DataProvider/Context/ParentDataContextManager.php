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
use AthosCommerce\Feed\Model\Feed\Context\StoreContextManager;
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
    /**
     * @var StoreContextManager
     */
    private $storeContextManager;
    /** @var int[] */
    private $loadedParentIds = [];

    /**
     * @param ParentProductCollection $parentProductCollection
     * @param StoreContextManager $storeContextManager
     */
    public function __construct(
        ParentProductCollection $parentProductCollection,
        StoreContextManager $storeContextManager,
    ) {
        $this->parentProductCollection = $parentProductCollection;
        $this->storeContextManager = $storeContextManager;
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
        $storeId = $this->getCurrentStoreId();
        $loaded = $this->loadedParentIds[$storeId] ?? [];
        $loadParentIds = array_values(array_diff(array_unique($allParentIds), $loaded));
        if (!$loadParentIds) {
            return $this->loadedParentIds[$storeId] ?? [];
        }

        $parentCollection = $this->parentProductCollection->execute(
            $loadParentIds,
            $feedSpecification
        );

        /** @var ProductInterface $parentProduct */
        foreach ($parentCollection as $parentProduct) {
            $parentId = (int)$parentProduct->getId();
            $this->productData[$storeId][$parentId] = $parentProduct;
            $this->loadedParentIds[$storeId][] = $parentId;
        }

        return $this->productData[$storeId];
    }

    /**
     * @param int $productId
     *
     * @return ProductInterface[]|null
     */
    public function getParentsDataByProductId(int $parentId, ?int $storeId = null)
    {
        $storeId = $this->getCurrentStoreId();

        return $this->productData[$storeId][$parentId] ?? null;
    }

    /**
     * @return int
     */
    private function getCurrentStoreId(): int
    {
        return (int)$this->storeContextManager->getStoreFromContext()->getId();
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
