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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Option\Collection as OptionCollection;
use Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory as OptionCollectionFactory;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\GroupedProduct\Model\Product\Type\Grouped as Grouped;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\OptionsProvider;

class OptionsProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MetadataPool
     */
    private $metadataPoolMock;

    /**
     * @var OptionCollectionFactory
     */
    private $optionCollectionFactoryMock;

    /**
     * @var StoreManagerInterface
     */
    private $storeManagerMock;

    private $optionsProvider;

    public function setUp(): void
    {
        $this->metadataPoolMock = $this->createMock(MetadataPool::class);
        $this->optionCollectionFactoryMock = $this->createMock(OptionCollectionFactory::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->optionsProvider = new OptionsProvider(
            $this->metadataPoolMock,
            $this->optionCollectionFactoryMock,
            $this->storeManagerMock
        );
    }

    public function testGetData()
    {
        $optionTitle = 'test_title';
        $valueTitle = 'test_value_title';
        $collectionMock = $this->createMock(OptionCollection::class);
        $optionMock = $this->createMock(Product\Option::class);
        $valueMock = $this->createMock(Product\Option::class);
        $storeMock = $this->createMock(Store::class);
        $storeId = 1;
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityMetadataMock = $this->getMockForAbstractClass(EntityMetadataInterface::class);
        $products = [
            [
                'product_model' => $productMock
            ]
        ];
        $options = [$optionMock];
        $values = [$valueMock];
        $this->metadataPoolMock->expects($this->any())
            ->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($entityMetadataMock);
        $entityMetadataMock->expects($this->any())
            ->method('getLinkField')
            ->willReturn('entity_id');
        $productMock->expects($this->any())
            ->method('getData')
            ->with('entity_id')
            ->willReturn(1);
        $feedSpecificationMock->expects($this->once())
            ->method('getStoreCode')
            ->willReturn('default');

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        $storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);
        $this->optionCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);
        $collectionMock->expects($this->at(0))
            ->method('addFieldToFilter')
            ->with('product_id', ['in' => [1]])
            ->willReturnSelf();
        $collectionMock->expects($this->at(1))
            ->method('addFieldToFilter')
            ->with('type', 'drop_down')
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('addTitleToResult')
            ->with(1)
            ->willReturnSelf();
        $collectionMock->expects($this->any())
            ->method('setOrder')
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('addValuesToResult')
            ->with(1)
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($options));
        $optionMock->expects($this->once())
            ->method('__call')
            ->with('getProductId')
            ->willReturn(1);
        $optionMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($optionTitle);
        $optionMock->expects($this->once())
            ->method('getValues')
            ->willReturn($values);
        $valueMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($valueTitle);

        $this->assertSame(
            [array_merge(
                $products[0],
                [
                    'option_' . $optionTitle => [
                        $valueTitle
                    ]
                ]
            )],
            $this->optionsProvider->getData($products, $feedSpecificationMock)
        );
    }
}
