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

use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\AttributesProviderInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\AttributesProvider;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\ValueProcessor;
use AthosCommerce\Feed\Model\Feed\SystemFieldsList;

class AttributesProviderTest extends \PHPUnit\Framework\TestCase
{
    private $systemFieldsListMock;

    private $valueProcessorMock;

    private $attributesProviderMock;

    private $attributesProvider;

    public function setUp(): void
    {
        $this->systemFieldsListMock = $this->createMock(SystemFieldsList::class);
        $this->valueProcessorMock = $this->createMock(ValueProcessor::class);
        $this->attributesProviderMock = $this->createMock(AttributesProviderInterface::class);
        $this->attributesProvider = new AttributesProvider(
            $this->systemFieldsListMock,
            $this->valueProcessorMock,
            $this->attributesProviderMock
        );
    }

    public function testGetData()
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $productAttributeInterfaceMock = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributes = [
            $productAttributeInterfaceMock
        ];
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $products = [
            [
                'product_model' => $productMock
            ],
            [
                'product_model' => $productMock
            ]
        ];
        $this->attributesProviderMock->expects($this->once())
            ->method('getAttributes')
            ->with($feedSpecificationMock)
            ->willReturn($attributes);

        $productAttributeInterfaceMock->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn('code');
        $productMock->expects($this->any())
            ->method('getData')
            ->willReturn(
                [
                    'code' => 'data',
                    'test1' => 'data2'
                ]
            );
        $this->valueProcessorMock->expects($this->at(0))
            ->method('getValue')
            ->willReturn('code');
        $this->valueProcessorMock->expects($this->at(1))
            ->method('getValue')
            ->willReturn('code1');
        $this->assertSame(
            [
                [
                    'product_model' => $productMock,
                    'code' => 'code',
                    'test1' => 'data2'
                ],
                [
                    'product_model' => $productMock,
                    'code' => 'code1',
                    'test1' => 'data2'
                ]
            ],
            $this->attributesProvider->getData($products, $feedSpecificationMock)
        );
    }
}
