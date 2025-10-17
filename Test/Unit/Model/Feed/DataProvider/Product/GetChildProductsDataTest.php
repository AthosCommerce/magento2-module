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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Product;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Pricing\PriceInfoInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\ValueProcessor;
use AthosCommerce\Feed\Model\Feed\DataProvider\Product\GetChildProductsData;

class GetChildProductsDataTest extends \PHPUnit\Framework\TestCase
{
    private $valueProcessorMock;

    private $getChildProductsData;

    public function setUp(): void
    {
        $this->valueProcessorMock = $this->createMock(ValueProcessor::class);
        $this->getChildProductsData = new GetChildProductsData($this->valueProcessorMock);
    }

    public function testGetProductData()
    {
        $priceInterfaceMock = $this->createMock(PriceInterface::class);
        $finalPriceMock = $this->createMock(FinalPrice::class);
        $priceInfoInterfaceMock = $this->getMockForAbstractClass(PriceInfoInterface::class);
        $childAttributeMock = $this->createMock(Attribute::class);
        $childAttributeMockSecond = $this->createMock(Attribute::class);
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn([]);
        $childProductMock = $this->createMock(Product::class);
        $childProductMockSecond = $this->createMock(Product::class);
        $childProducts = [
            $childProductMock,
            $childProductMockSecond
        ];
        $childAttributeCode = 'child_code_1';
        $childSecondAttributeCode = 'child_code_2';
        $childAttributeMock->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn($childAttributeCode);
        $childProductMock->expects($this->at(0))
            ->method('getData')
            ->with($childAttributeCode)
            ->willReturn('test_value_1');
        $this->valueProcessorMock->expects($this->at(0))
            ->method('getValue')
            ->with($childAttributeMock, 'test_value_1')
            ->willReturn('test_value_1');
        $childProductMock->expects($this->at(1))
            ->method('getData')
            ->with($childSecondAttributeCode)
            ->willReturn('test_value_2');
        $this->valueProcessorMock->expects($this->at(1))
            ->method('getValue')
            ->with($childAttributeMock, 'test_value_2')
            ->willReturn('test_value_2');
        $childProductMock->expects($this->any())
            ->method('getSku')
            ->willReturn('child_sku_1');
        $childProductMock->expects($this->any())
            ->method('getName')
            ->willReturn('child_name_1');
        $feedSpecificationMock->expects($this->any())
            ->method('getIncludeChildPrices')
            ->willReturn(true);
        $childProductMock->expects($this->once())
            ->method('getPriceInfo')
            ->willReturn($priceInfoInterfaceMock);
        $priceInfoInterfaceMock->expects($this->any())
            ->method('getPrice')
            ->with(FinalPrice::PRICE_CODE)
            ->willReturn($finalPriceMock);
        $finalPriceMock->expects($this->any())
            ->method('getMinimalPrice')
            ->willReturn($priceInterfaceMock);
        $priceInterfaceMock->expects($this->at(0))
            ->method('getValue')
            ->willReturn(3.0);

        $childAttributeMockSecond->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn($childSecondAttributeCode);
        $childProductMockSecond->expects($this->at(0))
            ->method('getData')
            ->with($childAttributeCode)
            ->willReturn('test_value_3');
        $this->valueProcessorMock->expects($this->at(2))
            ->method('getValue')
            ->with($childAttributeMock, 'test_value_3')
            ->willReturn('test_value_3');
        $childProductMockSecond->expects($this->at(1))
            ->method('getData')
            ->with($childSecondAttributeCode)
            ->willReturn('test_value_4');
        $this->valueProcessorMock->expects($this->at(3))
            ->method('getValue')
            ->with($childAttributeMockSecond, 'test_value_4')
            ->willReturn('test_value_4');
        $childProductMockSecond->expects($this->any())
            ->method('getSku')
            ->willReturn('child_sku_2');
        $childProductMockSecond->expects($this->any())
            ->method('getName')
            ->willReturn('child_name_2');
        $childProductMockSecond->expects($this->once())
            ->method('getPriceInfo')
            ->willReturn($priceInfoInterfaceMock);
        $priceInterfaceMock->expects($this->at(1))
            ->method('getValue')
            ->willReturn(3.33);

        $this->assertSame(
            [
                'child_code_1' => [
                    0 => 'test_value',
                    1 => 'test_value_1',
                    2 => 'test_value_3'
                ],
                'child_code_2' => [
                    0 => 'test_value_1',
                    1 => 'test_value_2',
                    2 => 'test_value_4'
                ],
                'child_sku' => [
                    0 => 'child_sku_1',
                    1 => 'child_sku_2'
                ],
                'child_name' => [
                    0 => 'child_name_1',
                    1 => 'child_name_2'
                ],
                'child_final_price' => [
                    0 => 3.0,
                    1 => 3.33
                ]
            ],
            $this->getChildProductsData->getProductData(
                [
                    'child_code_1' => 'test_value',
                    'child_code_2' => 'test_value_1',
                ],
                $childProducts,
                [
                    $childAttributeMock,
                    $childAttributeMockSecond,
                ],
                $feedSpecificationMock
            )
        );
    }
}
