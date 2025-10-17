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

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Configurable;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\Collection;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\CollectionFactory;
use Psr\Log\LoggerInterface;
class GetChildCollection
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var Status
     */
    private $status;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * GetChildCollection constructor.
     * @param CollectionFactory $collectionFactory
     * @param Status $status
     * @param LoggerInterface $logger
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        Status $status,
        LoggerInterface $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->status = $status;
        $this->logger = $logger;
    }

    /**
     * @param Product[] $products
     * @param string[] $attributeCodes
     * @return Collection
     */
    public function execute(
        array $products,
        array $attributeCodes = []
    ) : Collection {
        $collection = $this->collectionFactory->create();

        $productIds = [];
        $duplicateIds = []; // Array to store duplicates
        $seenIds = []; // Array to track seen IDs

        foreach ($products as $product) {
            $productId = $product->getId();

            if (in_array($productId, $seenIds)) {
                // If  already seen this ID, add it to duplicates
                $duplicateIds[] = $productId;
            } else {
                // Otherwise, add it to the seen IDs and product IDs
                $seenIds[] = $productId;
                $productIds[] = $productId;
            }
        }

        // Log duplicates if any
        if (!empty($duplicateIds)) {
            $this->logger->warning('Duplicate product IDs found: ' . implode(', ', $duplicateIds));
        }

        $productIds = array_unique($productIds);

        $defaultAttributes = [
            ProductInterface::STATUS,
            ProductInterface::SKU,
            ProductInterface::NAME,
            'special_price',
            'special_to_date',
            'special_from_date'
        ];

        $attributeCodes = array_unique(array_merge($attributeCodes, $defaultAttributes));
        $collection->addAttributeToSelect($attributeCodes);
        $collection->addAttributeToFilter(
            ProductInterface::STATUS,
            ['in' => $this->status->getVisibleStatusIds()]
        );

        if (!empty($productIds)) {
            $collection->addFieldToFilter('entity_id', ['in' => $productIds]);
        }

        $collection->addPriceData();

        return $collection;
    }
}
