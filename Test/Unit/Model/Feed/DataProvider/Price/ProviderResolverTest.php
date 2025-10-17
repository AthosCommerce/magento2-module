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
use AthosCommerce\Feed\Model\Feed\DataProvider\Price\BasePriceProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Price\ProviderResolver;

class ProviderResolverTest extends \PHPUnit\Framework\TestCase
{
    private $basePriceProviderMock;

    private $providerResolver;

    public function setUp(): void
    {
        $this->basePriceProviderMock = $this->createMock(BasePriceProvider::class);
        $this->providerResolver = new ProviderResolver($this->basePriceProviderMock);
    }

    public function testResolve()
    {
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('simple');

        $this->assertSame(
            $this->basePriceProviderMock,
            $this->providerResolver->resolve($productMock)
        );
    }
}
