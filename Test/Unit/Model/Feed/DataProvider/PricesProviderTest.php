<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\DataProvider\Price\PriceProviderInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Price\ProviderResolverInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\PricesProvider;
use Magento\Catalog\Model\Product;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PricesProviderTest extends TestCase
{
    /**
     * @var Json|MockObject
     */
    private $jsonMock;

    /**
     * @var ProviderResolverInterface|MockObject
     */
    private $priceProviderResolverMock;

    /**
     * @var ParentVariantResolver|MockObject
     */
    private $parentVariantResolverMock;

    /**
     * @var PricesProvider
     */
    private $pricesProvider;

    protected function setUp(): void
    {
        $this->jsonMock = $this->createMock(Json::class);
        $this->priceProviderResolverMock = $this->createMock(ProviderResolverInterface::class);
        $this->parentVariantResolverMock = $this->createMock(ParentVariantResolver::class);

        $this->pricesProvider = new PricesProvider(
            $this->jsonMock,
            $this->priceProviderResolverMock,
            $this->parentVariantResolverMock
        );
    }

    public function testGetData(): void
    {
        $priceProviderMock = $this->createMock(PriceProviderInterface::class);
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $productMock = $this->createMock(Product::class);

        $products = [
            [
                'product_model' => $productMock,
            ],
        ];

        $tierPrice = ['test' => 2.33];
        $prices = [
            'regular_price' => 1.0,
            'final_price' => 3.33,
            'max_price' => 3.33,
        ];

        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn([]);

        $this->priceProviderResolverMock->expects($this->once())
            ->method('resolve')
            ->with($productMock)
            ->willReturn($priceProviderMock);

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->with($products[0], $productMock)
            ->willReturn(null);

        $priceProviderMock->expects($this->once())
            ->method('getPrices')
            ->with($productMock, [], null)
            ->willReturn($prices);

        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeTierPricing')
            ->willReturn(true);

        $productMock->expects($this->once())
            ->method('getTierPrice')
            ->willReturn($tierPrice);

        $this->jsonMock->expects($this->once())
            ->method('serialize')
            ->with($tierPrice)
            ->willReturn(json_encode($tierPrice));

        $this->assertSame(
            [array_merge($products[0], array_merge($prices, ['tier_pricing' => json_encode($tierPrice)]))],
            $this->pricesProvider->getData($products, $feedSpecificationMock)
        );
    }
}
