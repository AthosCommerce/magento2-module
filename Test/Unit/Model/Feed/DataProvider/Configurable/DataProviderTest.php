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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Configurable;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute as ConfigurableAttribute;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\AttributesProviderInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\ChildAttributesProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\ValueProcessor;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\DataProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\GetAttributesCollection;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\GetChildCollection;
use AthosCommerce\Feed\Model\Feed\DataProvider\Product\ChildStorage;
use AthosCommerce\Feed\Model\Feed\DataProvider\Product\GetChildProductsData;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute\Collection;
use AthosCommerce\Feed\Model\Feed\Specification\Feed;

class DataProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GetAttributesCollection
     */
    private $getAttributesCollectionMock;

    /**
     * @var GetChildCollection
     */
    private $getChildCollectionMock;

    /**
     * @var MetadataPool
     */
    private $metadataPoolMock;

    /**
     * @var ChildAttributesProvider
     */
    private $childAttributesProviderMock;

    /**
     * @var GetChildProductsData
     */
    private $getChildProductsDataMock;

    /**
     * @var ValueProcessor
     */
    private $valueProcessorMock;

    /**
     * @var ChildStorage
     */
    private $childStorageMock;

    /**
     * @var AttributesProviderInterface[]
     */
    private $attributesProviderMock;

    private $dataProvider;

    public function setUp(): void
    {
        $this->getAttributesCollectionMock = $this->createMock(GetAttributesCollection::class);
        $this->getChildCollectionMock = $this->createMock(GetChildCollection::class);
        $this->metadataPoolMock = $this->createMock(MetadataPool::class);
        $this->childAttributesProviderMock = $this->createMock(ChildAttributesProvider::class);
        $this->getChildProductsDataMock = $this->createMock(GetChildProductsData::class);
        $this->valueProcessorMock = $this->createMock(ValueProcessor::class);
        $this->childStorageMock = $this->createMock(ChildStorage::class);
        $this->attributesProviderMock = $this->createMock(AttributesProviderInterface::class);
        $this->dataProvider = new DataProvider(
            $this->getAttributesCollectionMock,
            $this->getChildCollectionMock,
            $this->metadataPoolMock,
            $this->childAttributesProviderMock,
            $this->getChildProductsDataMock,
            $this->valueProcessorMock,
            $this->childStorageMock,
            [$this->attributesProviderMock]
        );
    }

    public function testGetById()
    {
        $productMock = $this->createMock(Product::class);
        $this->childStorageMock->expects($this->once())
            ->method('getById')
            ->with(1)
            ->willReturn([$productMock]);

        $this->assertSame([$productMock], $this->dataProvider->getById(1));
    }

    public function testGetAllChildProducts()
    {
        $configurableCollectionMock = $this->createMock(
            \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\Collection::class
        );
        $feedSpecificationMock = $this->createMock(Feed::class);
        $configurableAttributeMock = $this->createMock(ConfigurableAttribute::class);
        $abstractAttributeMock = $this->createMock(AbstractAttribute::class);
        $configurableAttributeMockSecond = $this->createMock(ConfigurableAttribute::class);
        $abstractAttributeMockSecond = $this->createMock(AbstractAttribute::class);
        $abstractAttributeMockSpecification = $this->createMock(AbstractAttribute::class);
        $attributeCollectionMock = $this->createMock(Collection::class);
        $productMock = $this->createMock(Product::class);
        $childProductMock = $this->createMock(Product::class);
        $entityMetadataMock = $this->getMockForAbstractClass(EntityMetadataInterface::class);
        $products = [
            ['product_model' => $productMock],
            ['product_model' => $productMock]
        ];
        $productMock->expects($this->any())
            ->method('getTypeId')
            ->willReturn(Configurable::TYPE_CODE);
        $this->metadataPoolMock->expects($this->any())
            ->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($entityMetadataMock);
        $entityMetadataMock->expects($this->any())
            ->method('getLinkField')
            ->willReturn('entity_id');
        $productMock->expects($this->at(1))
            ->method('getData')
            ->with('entity_id')
            ->willReturn(1);
        $productMock->expects($this->at(3))
            ->method('getData')
            ->with('entity_id')
            ->willReturn(2);
        $this->childStorageMock->expects($this->any())
            ->method('get')
            ->willReturn([]);
        $this->getAttributesCollectionMock->expects($this->once())
            ->method('execute')
            ->with([1 => $productMock, 2 => $productMock])
            ->willReturn($attributeCollectionMock);
        $attributeCollectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$configurableAttributeMock, $configurableAttributeMockSecond]);
        $configurableAttributeMock->expects($this->any())
            ->method('getAttributeId')
            ->willReturn(1);
        $configurableAttributeMock->expects($this->any())
            ->method('__call')
            ->with('getProductAttribute')
            ->willReturn($abstractAttributeMock);
        $configurableAttributeMockSecond->expects($this->any())
            ->method('getAttributeId')
            ->willReturn(2);
        $configurableAttributeMockSecond->expects($this->any())
            ->method('__call')
            ->with('getProductAttribute')
            ->willReturn($abstractAttributeMockSecond);
        $this->childAttributesProviderMock->expects($this->once())
            ->method('getAttributes')
            ->with($feedSpecificationMock)
            ->willReturn([$abstractAttributeMockSpecification]);
        $abstractAttributeMockSpecification->expects($this->any())
            ->method('getAttributeId')
            ->willReturn(3);
        $abstractAttributeMock->expects($this->once())
            ->method('getAttributeCode')
            ->willReturn('code_1');
        $abstractAttributeMockSecond->expects($this->once())
            ->method('getAttributeCode')
            ->willReturn('code_2');
        $abstractAttributeMockSpecification->expects($this->once())
            ->method('getAttributeCode')
            ->willReturn('code_3');
        $this->getChildCollectionMock->expects($this->once())
            ->method('execute')
            ->with([1 => $productMock, 2 => $productMock], [1 => 'code_2', 2 => 'code_3', 0 => 'code_1'])
            ->willReturn($configurableCollectionMock);
        $configurableCollectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$childProductMock, $childProductMock, $childProductMock]);
        $childProductMock->expects($this->at(0))
            ->method('__call')
            ->with('getParentId')
            ->willReturn(1);
        $childProductMock->expects($this->at(1))
            ->method('__call')
            ->with('getParentId')
            ->willReturn(1);
        $childProductMock->expects($this->at(2))
            ->method('__call')
            ->with('getParentId')
            ->willReturn(2);
        $childProductMock->expects($this->at(3))
            ->method('__call')
            ->with('getParentId')
            ->willReturn(2);
        $childProductMock->expects($this->at(4))
            ->method('__call')
            ->with('getParentId')
            ->willReturn(3);
        $childProductMock->expects($this->at(5))
            ->method('__call')
            ->with('getParentId')
            ->willReturn(3);
        $this->childStorageMock->expects($this->once())
            ->method('set')
            ->with([
                1 => [$childProductMock],
                2 => [$childProductMock],
                3 => [$childProductMock]
            ]);

        $this->assertSame(
            [
                1 => [$childProductMock],
                2 => [$childProductMock],
                3 => [$childProductMock]
            ],
            $this->dataProvider->getAllChildProducts($products, $feedSpecificationMock)
        );
    }

    public function testGetConfigurableProducts()
    {
        $productMock = $this->createMock(Product::class);
        $entityMetadataMock = $this->getMockForAbstractClass(EntityMetadataInterface::class);
        $products = [
            ['product_model' => $productMock],
            ['product_model' => $productMock]
        ];
        $productMock->expects($this->any())
            ->method('getTypeId')
            ->willReturn(Configurable::TYPE_CODE);
        $this->metadataPoolMock->expects($this->any())
            ->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($entityMetadataMock);
        $entityMetadataMock->expects($this->any())
            ->method('getLinkField')
            ->willReturn('entity_id');
        $productMock->expects($this->at(1))
            ->method('getData')
            ->with('entity_id')
            ->willReturn(1);
        $productMock->expects($this->at(3))
            ->method('getData')
            ->with('entity_id')
            ->willReturn(2);

        $this->assertSame(
            [
                1 => $productMock,
                2 => $productMock
            ],
            $this->dataProvider->getConfigurableProducts($products)
        );
    }

    public function testGetConfigurableAttributes()
    {
        $configurableAttributeMock = $this->createMock(ConfigurableAttribute::class);
        $configurableAttributeMockSecond = $this->createMock(ConfigurableAttribute::class);
        $configurableAttributeMockSpecification = $this->createMock(ConfigurableAttribute::class);
        $abstractAttributeMock = $this->createMock(AbstractAttribute::class);
        $abstractAttributeMockSpecification = $this->createMock(AbstractAttribute::class);
        $abstractAttributeMockSpecificationSecond = $this->createMock(AbstractAttribute::class);
        $abstractAttributeMockSecond = $this->createMock(AbstractAttribute::class);
        $attributeCollectionMock = $this->createMock(Collection::class);
        $feedSpecificationMock = $this->createMock(Feed::class);
        $productMock = $this->createMock(Product::class);
        $configurableProducts = [$productMock, $productMock];
        $this->getAttributesCollectionMock->expects($this->once())
            ->method('execute')
            ->with($configurableProducts)
            ->willReturn($attributeCollectionMock);
        $attributeCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$configurableAttributeMock, $configurableAttributeMockSecond]));

        $configurableAttributeMock->expects($this->at(0))
            ->method('__call')
            ->with('getProductAttribute')
            ->willReturn($abstractAttributeMock);
        $configurableAttributeMockSecond->expects($this->at(0))
            ->method('__call')
            ->with('getProductAttribute')
            ->willReturn($abstractAttributeMockSecond);

        $configurableAttributeMock->expects($this->any())
            ->method('getProductId')
            ->willReturn(1);
        $abstractAttributeMock->expects($this->any())
            ->method('getAttributeId')
            ->willReturn(1);
        $configurableAttributeMockSecond->expects($this->any())
            ->method('getProductId')
            ->willReturn(2);
        $abstractAttributeMockSecond->expects($this->any())
            ->method('getAttributeId')
            ->willReturn(2);
        $configurableAttributeMockSpecification->expects($this->any())
            ->method('getProductId')
            ->willReturn(2);
        $abstractAttributeMockSpecification->expects($this->any())
            ->method('getAttributeId')
            ->willReturn(3);

        $this->childAttributesProviderMock->expects($this->once())
            ->method('getAttributes')
            ->with($feedSpecificationMock)
            ->willReturn([$configurableAttributeMockSpecification, $configurableAttributeMockSpecification]);
        $configurableAttributeMockSpecification->expects($this->any())
            ->method('getAttributeId')
            ->willReturn(4);

        $this->assertSame(
            [
                1 => [
                    1 => $abstractAttributeMock,
                    4 => $configurableAttributeMockSpecification
                ],
                2 => [
                    2 => $abstractAttributeMockSecond,
                    4 => $configurableAttributeMockSpecification
                ]
            ],
            $this->dataProvider->getConfigurableAttributes($configurableProducts, $feedSpecificationMock)
        );
    }
}
