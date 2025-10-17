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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Price;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface;
use Magento\Framework\Pricing\Amount\AmountInterface;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Pricing\PriceInfoInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\DataProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Price\ConfigurablePriceProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\PricesProvider;

class ConfigurablePriceProviderTest extends \PHPUnit\Framework\TestCase
{
    private $dataProviderMock;

    private $configurableOptionsProviderMock;

    private $configurablePriceProvider;

    public function setUp(): void
    {
        $this->dataProviderMock = $this->createMock(DataProvider::class);
        $this->configurableOptionsProviderMock =
            $this->createMock(ConfigurableOptionsProviderInterface::class);
        $this->configurablePriceProvider = new ConfigurablePriceProvider(
            $this->dataProviderMock,
            $this->configurableOptionsProviderMock
        );
    }

    public function testGetPrices()
    {
        $amountInterfaceMock = $this->createMock(AmountInterface::class);
        $priceInterfaceMock = $this->getMockForAbstractClass(PriceInterface::class);
        $regularPriceMock = $this->createMock(RegularPrice::class);
        $finalPriceMock = $this->createMock(FinalPrice::class);
        $priceInfoInterfaceMock = $this->getMockForAbstractClass(PriceInfoInterface::class);
        $childMock = $this->createMock(Product::class);
        $childMockSecond = $this->createMock(Product::class);
        $childProducts = [
            $childMock,
            $childMockSecond
        ];
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productMock->expects($this->any())
            ->method('getPriceInfo')
            ->willReturn($priceInfoInterfaceMock);
        $priceInfoInterfaceMock->expects($this->at(0))
            ->method('getPrice')
            ->with(FinalPrice::PRICE_CODE)
            ->willReturn($finalPriceMock);
        $priceInfoInterfaceMock->expects($this->at(1))
            ->method('getPrice')
            ->with(RegularPrice::PRICE_CODE)
            ->willReturn($regularPriceMock);
        $finalPriceMock->expects($this->once())
            ->method('getMinimalPrice')
            ->willReturn($priceInterfaceMock);

        $regularPriceMock->expects($this->once())
            ->method('getValue')
            ->willReturn(0.5);
        $priceInterfaceMock->expects($this->any())
            ->method('getValue')
            ->willReturn(1.0);

        $productMock->expects($this->once())
            ->method('__call')
            ->with('hasMaxPrice')
            ->willReturn(null);

        $productMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $this->dataProviderMock->expects($this->once())
            ->method('getById')
            ->with(1)
            ->willReturn($childProducts);
        $childMock->expects($this->any())
            ->method('getPriceInfo')
            ->willReturn($priceInfoInterfaceMock);
        $childMockSecond->expects($this->any())
            ->method('getPriceInfo')
            ->willReturn($priceInfoInterfaceMock);
        $priceInfoInterfaceMock->expects($this->at(2))
            ->method('getPrice')
            ->with(FinalPrice::PRICE_CODE)
            ->willReturn($finalPriceMock);
        $priceInfoInterfaceMock->expects($this->at(3))
            ->method('getPrice')
            ->with(FinalPrice::PRICE_CODE)
            ->willReturn($finalPriceMock);
        $finalPriceMock->expects($this->any())
            ->method('getAmount')
            ->willReturn($amountInterfaceMock);
        $amountInterfaceMock->expects($this->at(0))
            ->method('getValue')
            ->willReturn(2.0);
        $amountInterfaceMock->expects($this->at(1))
            ->method('getValue')
            ->willReturn(2.5);
        $amountInterfaceMock->expects($this->at(2))
            ->method('getValue')
            ->willReturn(2.5);

        $this->assertSame(
            [
                FinalPrice::PRICE_CODE => 1.0,
                RegularPrice::PRICE_CODE => 0.5,
                PricesProvider::MAX_PRICE_KEY => 2.5
            ],
            $this->configurablePriceProvider->getPrices($productMock, [])
        );
    }
}
