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

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Grouped;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Link as LinkModel;
use Magento\Catalog\Model\Product\LinkFactory;
use Magento\Catalog\Model\ResourceModel\Product\Link\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory;
use Magento\CatalogInventory\Helper\Stock;
use Magento\CatalogInventory\Model\Configuration;
use Magento\GroupedProduct\Model\ResourceModel\Product\Link;

class GetChildCollection
{
    /**
     * @var LinkFactory
     */
    private $linkFactory;

    private $defaultAttributes = [
        ProductInterface::NAME,
        ProductInterface::PRICE,
        'special_price',
        'special_from_date',
        'special_to_date',
        'tax_class_id',
        ProductInterface::SKU,
        ProductInterface::STATUS
    ];
    /**
     * @var Status
     */
    private $status;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var Stock
     */
    private $stockHelper;
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * GetChildCollection constructor.
     * @param LinkFactory $linkFactory
     * @param Status $status
     * @param CollectionFactory $collectionFactory
     * @param Stock $stockHelper
     * @param Configuration $configuration
     * @param array $defaultAttributes
     */
    public function __construct(
        LinkFactory $linkFactory,
        Status $status,
        CollectionFactory $collectionFactory,
        Stock $stockHelper,
        Configuration $configuration,
        array $defaultAttributes = []
    ) {
        $this->linkFactory = $linkFactory;
        $this->defaultAttributes = array_unique(array_merge($this->defaultAttributes, $defaultAttributes));
        $this->status = $status;
        $this->collectionFactory = $collectionFactory;
        $this->stockHelper = $stockHelper;
        $this->configuration = $configuration;
    }

    /**
     * @param array $productIds
     * @param array $attributeCodes
     * @param int|null $storeId
     * @return Collection
     */
    public function execute(array $productIds, array $attributeCodes = [], ?int $storeId = null) : Collection
    {
        /** @var LinkModel $links */
        $links = $this->linkFactory->create(['productIds' => $productIds]);
        $links->setLinkTypeId(Link::LINK_TYPE_GROUPED);
        $links->getProductCollection();
        $attributeCodes = array_unique(array_merge($attributeCodes, $this->defaultAttributes));
        $collection = $this->collectionFactory->create(['productIds' => $productIds]);
        $collection->setLinkModel($links)
            ->setFlag('product_children', true)
            ->addAttributeToSelect($attributeCodes)
            ->addFilterByRequiredOptions()
            ->addAttributeToFilter(ProductInterface::STATUS, ['in' => $this->status->getVisibleStatusIds()])
            ->addProductFilter($productIds)
            ->addStoreFilter($storeId)
            ->addPriceData()
            ->setPositionOrder()
            ->setIsStrongMode();

        if ($this->configuration->isShowOutOfStock($storeId) != 1) {
            $this->stockHelper->addInStockFilterToCollection($collection);
        }
        $collection->getSelect()->group('e.entity_id');

        return $collection;
    }
}
