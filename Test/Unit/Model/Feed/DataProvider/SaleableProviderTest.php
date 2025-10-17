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

use Magento\Catalog\Model\Product;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\SaleableProvider;

class SaleableProviderTest extends \PHPUnit\Framework\TestCase
{
    private $saleableProvider;

    public function setUp(): void
    {
        $this->saleableProvider = new SaleableProvider();
    }

    public function testGetData()
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $productMock = $this->createMock(Product::class);
        $products = [
            [
                'product_model' => $productMock
            ]
        ];
        $productMock->expects($this->once())
            ->method('isSaleable')
            ->willReturn(true);

        $this->assertSame(
            [
                [
                    'product_model' => $productMock,
                    'saleable' => true
                ]
            ],
            $this->saleableProvider->getData($products, $feedSpecificationMock)
        );
    }
}
