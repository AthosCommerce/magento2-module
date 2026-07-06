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

use AthosCommerce\Feed\Model\Feed\DataProvider\Price\BasePriceProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\PricesProvider;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Pricing\PriceInfoInterface;
use PHPUnit\Framework\TestCase;

class BasePriceProviderTest extends TestCase
{
    private $typeMock;
    private $basePriceProvider;

    protected function setUp(): void
    {
        $this->typeMock = $this->createMock(Type::class);
        $this->basePriceProvider = new BasePriceProvider($this->typeMock);
    }

    public function testGetPrices(): void
    {
        $minimalPriceMock = $this->createMock(PriceInterface::class);
        $maximalPriceMock = $this->createMock(PriceInterface::class);
        $regularPriceMock = $this->createMock(RegularPrice::class);
        $finalPriceMock = $this->createMock(FinalPrice::class);
        $priceInfoMock = $this->createMock(PriceInfoInterface::class);
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productMock->expects($this->exactly(3))
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

        $finalPriceMock->expects($this->once())
            ->method('getMaximalPrice')
            ->willReturn($maximalPriceMock);

        $regularPriceMock->expects($this->once())
            ->method('getValue')
            ->willReturn(0.5);

        $minimalPriceMock->expects($this->once())
            ->method('getValue')
            ->willReturn(1.0);

        $maximalPriceMock->expects($this->once())
            ->method('getValue')
            ->willReturn(2.0);

        $this->assertSame(
            [
                FinalPrice::PRICE_CODE => 1.0,
                RegularPrice::PRICE_CODE => 0.5,
                PricesProvider::MAX_PRICE_KEY => 2.0,
            ],
            $this->basePriceProvider->getPrices($productMock, [])
        );
    }
}
