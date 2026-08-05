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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Price;

use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\DataProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Price\ConfigurablePriceProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\PricesProvider;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface;
use Magento\Framework\Pricing\Amount\AmountInterface;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Pricing\PriceInfoInterface;
use PHPUnit\Framework\TestCase;

class ConfigurablePriceProviderTest extends TestCase
{
    private $dataProviderMock;
    private $configurableOptionsProviderMock;
    private $configurablePriceProvider;

    protected function setUp(): void
    {
        $this->dataProviderMock = $this->createMock(DataProvider::class);
        $this->configurableOptionsProviderMock = $this->createMock(ConfigurableOptionsProviderInterface::class);

        $this->configurablePriceProvider = new ConfigurablePriceProvider(
            $this->dataProviderMock,
            $this->configurableOptionsProviderMock
        );
    }

    public function testGetPrices(): void
    {
        $minimalPriceMock = $this->createMock(PriceInterface::class);
        $regularPriceMock = $this->createMock(RegularPrice::class);
        $finalPriceMock = $this->createMock(FinalPrice::class);
        $priceInfoMock = $this->createMock(PriceInfoInterface::class);

        $amountMockChildOne = $this->createMock(AmountInterface::class);
        $amountMockChildTwo = $this->createMock(AmountInterface::class);

        $childMock = $this->createMock(Product::class);
        $childMockSecond = $this->createMock(Product::class);

        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->addMethods(['hasMaxPrice'])
            ->onlyMethods(['getPriceInfo', 'getId'])
            ->getMock();

        $productMock->expects($this->exactly(2))
            ->method('getPriceInfo')
            ->willReturn($priceInfoMock);

        $priceInfoMock->method('getPrice')
            ->willReturnMap([
                [FinalPrice::PRICE_CODE, $finalPriceMock],
                [RegularPrice::PRICE_CODE, $regularPriceMock],
            ]);

        $finalPriceMock->expects($this->once())
            ->method('getMinimalPrice')
            ->willReturn($minimalPriceMock);

        $minimalPriceMock->expects($this->once())
            ->method('getValue')
            ->willReturn(1.0);

        $regularPriceMock->expects($this->once())
            ->method('getValue')
            ->willReturn(0.5);

        $productMock->expects($this->once())
            ->method('hasMaxPrice')
            ->willReturn(false);

        $productMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->dataProviderMock->expects($this->once())
            ->method('getById')
            ->with(1)
            ->willReturn([$childMock, $childMockSecond]);

        $childPriceInfoOne = $this->createMock(PriceInfoInterface::class);
        $childFinalPriceOne = $this->createMock(FinalPrice::class);

        $childMock->expects($this->once())
            ->method('getPriceInfo')
            ->willReturn($childPriceInfoOne);

        $childPriceInfoOne->expects($this->once())
            ->method('getPrice')
            ->with(FinalPrice::PRICE_CODE)
            ->willReturn($childFinalPriceOne);

        $childFinalPriceOne->expects($this->once())
            ->method('getAmount')
            ->willReturn($amountMockChildOne);

        $amountMockChildOne->expects($this->once(2))
            ->method('getValue')
            ->willReturn(2.0);

        $childPriceInfoTwo = $this->createMock(PriceInfoInterface::class);
        $childFinalPriceTwo = $this->createMock(FinalPrice::class);

        $childMockSecond->expects($this->once())
            ->method('getPriceInfo')
            ->willReturn($childPriceInfoTwo);

        $childPriceInfoTwo->expects($this->once())
            ->method('getPrice')
            ->with(FinalPrice::PRICE_CODE)
            ->willReturn($childFinalPriceTwo);

        $childFinalPriceTwo->expects($this->once())
            ->method('getAmount')
            ->willReturn($amountMockChildTwo);

        $amountMockChildTwo->expects($this->exactly(2))
            ->method('getValue')
            ->willReturn(2.5);

        $this->assertSame(
            [
                FinalPrice::PRICE_CODE => 1.0,
                RegularPrice::PRICE_CODE => 0.5,
                PricesProvider::MAX_PRICE_KEY => 2.5,
            ],
            $this->configurablePriceProvider->getPrices($productMock, [])
        );
    }
}
