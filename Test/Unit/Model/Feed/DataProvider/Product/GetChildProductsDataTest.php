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

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\ValueProcessor;
use AthosCommerce\Feed\Model\Feed\DataProvider\Product\GetChildProductsData;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Pricing\PriceInfoInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetChildProductsDataTest extends TestCase
{
    /**
     * @var ValueProcessor&MockObject
     */
    private $valueProcessorMock;

    /**
     * @var GetChildProductsData
     */
    private $getChildProductsData;

    public function setUp(): void
    {
        $this->valueProcessorMock = $this->createMock(ValueProcessor::class);
        $this->getChildProductsData = new GetChildProductsData($this->valueProcessorMock);
    }

    public function testGetProductData(): void
    {
        $priceInterfaceMock = $this->createMock(PriceInterface::class);
        $finalPriceMock = $this->createMock(FinalPrice::class);
        $priceInfoInterfaceMock = $this->createMock(PriceInfoInterface::class);
        $childAttributeMock = $this->createMock(Attribute::class);
        $childAttributeMockSecond = $this->createMock(Attribute::class);
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $childProductMock = $this->createMock(Product::class);
        $childProductMockSecond = $this->createMock(Product::class);

        $childAttributeCode = 'child_code_1';
        $childSecondAttributeCode = 'child_code_2';

        $childProducts = [
            $childProductMock,
            $childProductMockSecond,
        ];

        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn([]);

        $feedSpecificationMock->expects($this->exactly(2))
            ->method('getIncludeChildPrices')
            ->willReturn(true);

        $childAttributeMock->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn($childAttributeCode);

        $childAttributeMockSecond->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn($childSecondAttributeCode);

        $firstChildGetDataCalls = [];
        $childProductMock->expects($this->exactly(2))
            ->method('getData')
            ->willReturnCallback(function ($code) use (&$firstChildGetDataCalls, $childAttributeCode, $childSecondAttributeCode) {
                $firstChildGetDataCalls[] = $code;

                if ($code === $childAttributeCode) {
                    return 'test_value_1';
                }

                if ($code === $childSecondAttributeCode) {
                    return 'test_value_2';
                }

                return null;
            });

        $secondChildGetDataCalls = [];
        $childProductMockSecond->expects($this->exactly(2))
            ->method('getData')
            ->willReturnCallback(function ($code) use (&$secondChildGetDataCalls, $childAttributeCode, $childSecondAttributeCode) {
                $secondChildGetDataCalls[] = $code;

                if ($code === $childAttributeCode) {
                    return 'test_value_3';
                }

                if ($code === $childSecondAttributeCode) {
                    return 'test_value_4';
                }

                return null;
            });

        $valueProcessorCalls = [];
        $this->valueProcessorMock->expects($this->exactly(4))
            ->method('getValue')
            ->willReturnCallback(function ($attribute, $value, $childProduct, $feedSpecification) use (
                &$valueProcessorCalls,
                $feedSpecificationMock
            ) {
                $valueProcessorCalls[] = [$attribute, $value, $childProduct, $feedSpecification];

                $this->assertSame($feedSpecificationMock, $feedSpecification);

                return $value;
            });

        $childProductMock->expects($this->any())
            ->method('getSku')
            ->willReturn('child_sku_1');

        $childProductMock->expects($this->any())
            ->method('getName')
            ->willReturn('child_name_1');

        $childProductMockSecond->expects($this->any())
            ->method('getSku')
            ->willReturn('child_sku_2');

        $childProductMockSecond->expects($this->any())
            ->method('getName')
            ->willReturn('child_name_2');

        $childProductMock->expects($this->once())
            ->method('getPriceInfo')
            ->willReturn($priceInfoInterfaceMock);

        $childProductMockSecond->expects($this->once())
            ->method('getPriceInfo')
            ->willReturn($priceInfoInterfaceMock);

        $priceInfoInterfaceMock->expects($this->exactly(2))
            ->method('getPrice')
            ->with(FinalPrice::PRICE_CODE)
            ->willReturn($finalPriceMock);

        $finalPriceMock->expects($this->exactly(2))
            ->method('getMinimalPrice')
            ->willReturn($priceInterfaceMock);

        $priceValues = [3.0, 3.33];
        $priceInterfaceMock->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnCallback(function () use (&$priceValues) {
                return array_shift($priceValues);
            });

        $this->assertSame(
            [
                'child_code_1' => [
                    0 => 'test_value',
                    1 => 'test_value_1',
                    2 => 'test_value_3',
                ],
                'child_code_2' => [
                    0 => 'test_value_1',
                    1 => 'test_value_2',
                    2 => 'test_value_4',
                ],
                'child_sku' => [
                    0 => 'child_sku_1',
                    1 => 'child_sku_2',
                ],
                'child_name' => [
                    0 => 'child_name_1',
                    1 => 'child_name_2',
                ],
                'child_final_price' => [
                    0 => 3.0,
                    1 => 3.33,
                ],
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

        $this->assertSame(
            [
                $childAttributeCode,
                $childSecondAttributeCode,
            ],
            $firstChildGetDataCalls
        );

        $this->assertSame(
            [
                $childAttributeCode,
                $childSecondAttributeCode,
            ],
            $secondChildGetDataCalls
        );

        $this->assertSame(
            [
                [$childAttributeMock, 'test_value_1', $childProductMock, $feedSpecificationMock],
                [$childAttributeMockSecond, 'test_value_2', $childProductMock, $feedSpecificationMock],
                [$childAttributeMock, 'test_value_3', $childProductMockSecond, $feedSpecificationMock],
                [$childAttributeMockSecond, 'test_value_4', $childProductMockSecond, $feedSpecificationMock],
            ],
            $valueProcessorCalls
        );
    }
}
