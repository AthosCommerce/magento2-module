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

namespace AthosCommerce\Feed\Model\Api;

use AthosCommerce\Feed\Api\ProductInfoInterface;
use AthosCommerce\Feed\Model\CollectionProcessor;
use AthosCommerce\Feed\Model\ItemsGenerator;

class ProductInfo implements ProductInfoInterface
{
    /**
     * @param CollectionProcessor $collectionProcessor
     * @param ItemsGenerator $itemsGenerator
     */
    public function __construct(
        CollectionProcessor $collectionProcessor,
        ItemsGenerator $itemsGenerator,
    )
    {
        $this->collectionProcessor = $collectionProcessor;
        $this->itemsGenerator = $itemsGenerator;
    }

    /**
     * @param int $productId
     * @param int $storeId
     *
     * @return array
     */
    public function getInfo(
        int $productId,
        int $storeId = 1
    ): array {
        $response = [];
        try {
            $productIds = $this->getParentOrChildIds($productId);
            $response['productIds'] = $productIds;
            $this->itemsGenerator->resetDataProviders($feedSpecification);
            //TODO:: Require to check with productTypes/StoreId.
            $productCollection = $this->collectionProcessor->getCollection($feedSpecification);
            $productCollection->addFieldToFilter('entity_id', ['in' => $productIds]);
            $productCollection->load();
            $this->collectionProcessor->processAfterLoad($productCollection, $feedSpecification);
            $items = $productCollection->getItems();
            if (!$items) {
                $response['productInfo'] = 'Provided items not available';

                return $product;
            }
            $itemsData = $this->itemsGenerator->generate($items, $feedSpecification);
            $this->itemsGenerator->resetDataProvidersAfterFetchItems($feedSpecification);
            $this->collectionProcessor->processAfterFetchItems($productCollection, $feedSpecification);
        } catch (Exception $e) {
            $response['productInfo'] = 'Not found due to exception';
            $response['message'] = $e->getMessage();

            return $product;
        }
        $product['productInfo'] = $itemsData;

        return $product;
    }

    /**
     * @param int $productId
     *
     * @return array
     */
    private function getParentOrChildIds(int $productId): array
    {
        $ids = [$productId];

        try {
            $childIds = [];
            $parentIds = [];
            $groupedParentIds = [];
            $groupedChildrenIds = [];
            //\AthosCommerce\Feed\Model\Feed\DataProvider\Parent\RelationsProvider::getConfigurableRelationIds
            //\AthosCommerce\Feed\Model\Feed\DataProvider\Parent\RelationsProvider::getGroupRelationIds
            //\AthosCommerce\Feed\Model\Feed\DataProvider\Parent\RelationsProvider::getConfigurableChildrenIds
            //\AthosCommerce\Feed\Model\Feed\DataProvider\Parent\RelationsProvider::getGroupedChildrenIds

            $childIds = array_merge(...
                array_values($childIds
                    ?: []));
            $ids = array_merge(
                $ids,
                $parentIds
                    ?: [],
                $groupedParentIds
                    ?: [],
                $childIds
                    ?: [],
                $groupedChildrenIds
                    ?: []
            );
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to resolve parent/child IDs',
                [
                    'method' => __METHOD__,
                    'productId' => $productId,
                    'message' => $e->getMessage(),
                ]
            );
        }

        return array_unique(array_filter($ids));
    }
}
