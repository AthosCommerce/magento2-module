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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Grouped;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Link as LinkModel;
use Magento\Catalog\Model\Product\LinkFactory;
use Magento\Catalog\Model\ResourceModel\Product\Link\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory;
use Magento\CatalogInventory\Helper\Stock;
use Magento\CatalogInventory\Model\Configuration;
use Magento\GroupedProduct\Model\ResourceModel\Product\Link;
use AthosCommerce\Feed\Model\Feed\DataProvider\Grouped\GetChildCollection;

class GetChildCollectionTest extends \PHPUnit\Framework\TestCase
{
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

    private $linkFactoryMock;

    private $statusMock;

    private $collectionFactoryMock;

    private $stockHelperMock;

    private $configurationMock;

    private $getChildCollection;

    public function setUp(): void
    {
        $this->linkFactoryMock = $this->createMock(LinkFactory::class);
        $this->statusMock = $this->createMock(Status::class);
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->stockHelperMock = $this->createMock(Stock::class);
        $this->configurationMock = $this->createMock(Configuration::class);
        $this->getChildCollection = new GetChildCollection(
            $this->linkFactoryMock,
            $this->statusMock,
            $this->collectionFactoryMock,
            $this->stockHelperMock,
            $this->configurationMock,
            $this->defaultAttributes
        );
    }

    public function testExecute()
    {
        $collectionMock = $this->createMock(Collection::class);
        $linkMock = $this->createMock(LinkModel::class);
        $this->linkFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($linkMock);
        $linkMock->expects($this->once())
            ->method('__call')
            ->with('setLinkTypeId', [Link::LINK_TYPE_GROUPED])
            ->willReturnSelf();
        $linkMock->expects($this->once())
            ->method('getProductCollection')
            ->willReturn($collectionMock);
        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);
        $collectionMock->expects($this->once())
            ->method('setLinkModel')
            ->with($linkMock)
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('setFlag')
            ->with('product_children', true)
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('addFilterByRequiredOptions')
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('addAttributeToFilter')
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('addProductFilter')
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('addStoreFilter')
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('addPriceData')
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('setPositionOrder')
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('setIsStrongMode')
            ->willReturnSelf();

        $this->configurationMock->expects($this->once())
            ->method('isShowOutOfStock')
            ->willReturn(0);

        $this->stockHelperMock->expects($this->once())
            ->method('addInStockFilterToCollection')
            ->with($collectionMock);

        $this->assertSame($collectionMock, $this->getChildCollection->execute([]));
    }
}
