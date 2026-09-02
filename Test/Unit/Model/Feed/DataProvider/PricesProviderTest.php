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
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
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
     * @var AthosCommerceLogger|MockObject
     */
    private $loggerMock;

    /**
     * @var PricesProvider
     */
    private $pricesProvider;

    protected function setUp(): void
    {
        $this->jsonMock = $this->createMock(Json::class);
        $this->priceProviderResolverMock = $this->createMock(ProviderResolverInterface::class);
        $this->parentVariantResolverMock = $this->createMock(ParentVariantResolver::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->pricesProvider = new PricesProvider(
            $this->jsonMock,
            $this->priceProviderResolverMock,
            $this->parentVariantResolverMock,
            $this->loggerMock
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

        $this->loggerMock->expects($this->exactly(2))
            ->method('info');

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

    public function testGetDataForStandaloneRowSkipsParentResolution(): void
    {
        $priceProviderMock = $this->createMock(PriceProviderInterface::class);
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $productMock = $this->createMock(Product::class);

        $products = [
            [
                'product_model' => $productMock,
                Constant::IS_STANDALONE_PRODUCT_KEY => true,
                Constant::IS_BELONG_TO_PARENT_KEY => false,
            ],
        ];

        $prices = [
            'regular_price' => 11.0,
            'final_price' => 9.5,
            'max_price' => 11.0,
        ];

        $this->loggerMock->expects($this->exactly(2))
            ->method('info');

        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn([]);

        $this->priceProviderResolverMock->expects($this->once())
            ->method('resolve')
            ->with($productMock)
            ->willReturn($priceProviderMock);

        $this->parentVariantResolverMock->expects($this->never())
            ->method('resolveParentProductForRow');

        $priceProviderMock->expects($this->once())
            ->method('getPrices')
            ->with($productMock, [], null)
            ->willReturn($prices);

        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeTierPricing')
            ->willReturn(false);

        $this->assertSame(
            [array_merge($products[0], $prices)],
            $this->pricesProvider->getData($products, $feedSpecificationMock)
        );
    }

    public function testGetDataForParentLinkedRowUsesResolvedParent(): void
    {
        $priceProviderMock = $this->createMock(PriceProviderInterface::class);
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $productMock = $this->createMock(Product::class);
        $resolvedParentMock = $this->createMock(Product::class);

        $products = [
            [
                'product_model' => $productMock,
                Constant::IS_STANDALONE_PRODUCT_KEY => false,
                Constant::IS_BELONG_TO_PARENT_KEY => true,
            ],
        ];

        $prices = [
            'regular_price' => 0.0,
            'final_price' => 1000.0,
            'max_price' => 1001.0,
        ];

        $this->loggerMock->expects($this->exactly(2))
            ->method('info');

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
            ->willReturn($resolvedParentMock);

        $priceProviderMock->expects($this->once())
            ->method('getPrices')
            ->with($productMock, [], $resolvedParentMock)
            ->willReturn($prices);

        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeTierPricing')
            ->willReturn(false);

        $this->assertSame(
            [array_merge($products[0], $prices)],
            $this->pricesProvider->getData($products, $feedSpecificationMock)
        );
    }

    public function testGetDataCachesResolvedParentForRepeatedRows(): void
    {
        $priceProviderMock = $this->createMock(PriceProviderInterface::class);
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $productMock = $this->createMock(Product::class);
        $resolvedParentMock = $this->createMock(Product::class);

        $products = [
            ['product_model' => $productMock, 'entity_id' => 1, Constant::IS_STANDALONE_PRODUCT_KEY => false],
            ['product_model' => $productMock, 'entity_id' => 1, Constant::IS_STANDALONE_PRODUCT_KEY => false],
        ];

        $productMock->method('getId')->willReturn(1);

        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);
        $feedSpecificationMock->method('getIncludeTierPricing')->willReturn(false);

        $this->priceProviderResolverMock->expects($this->exactly(2))
            ->method('resolve')
            ->with($productMock)
            ->willReturn($priceProviderMock);

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->with($products[0], $productMock)
            ->willReturn($resolvedParentMock);

        $priceProviderMock->expects($this->exactly(2))
            ->method('getPrices')
            ->with($productMock, [], $resolvedParentMock)
            ->willReturn([
                'regular_price' => 1.0,
                'final_price' => 2.0,
                'max_price' => 3.0,
            ]);

        $result = $this->pricesProvider->getData($products, $feedSpecificationMock);

        $this->assertSame(3.0, $result[0]['max_price']);
        $this->assertSame(3.0, $result[1]['max_price']);
    }

    public function testGetDataResolvesParentsPerRowContextForSameProduct(): void
    {
        $priceProviderMock = $this->createMock(PriceProviderInterface::class);
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $productMock = $this->createMock(Product::class);
        $firstParentMock = $this->createMock(Product::class);
        $secondParentMock = $this->createMock(Product::class);

        $products = [
            [
                'product_model' => $productMock,
                'entity_id' => 1,
                Constant::IS_STANDALONE_PRODUCT_KEY => false,
                Constant::RESOLVED_PARENT_ID_KEY => 10,
                Constant::RESOLVED_PARENT_SKU_KEY => 'parent-one',
            ],
            [
                'product_model' => $productMock,
                'entity_id' => 1,
                Constant::IS_STANDALONE_PRODUCT_KEY => false,
                Constant::RESOLVED_PARENT_ID_KEY => 11,
                Constant::RESOLVED_PARENT_SKU_KEY => 'parent-two',
            ],
        ];

        $productMock->method('getId')->willReturn(1);

        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);
        $feedSpecificationMock->method('getIncludeTierPricing')->willReturn(false);

        $this->priceProviderResolverMock->expects($this->exactly(2))
            ->method('resolve')
            ->with($productMock)
            ->willReturn($priceProviderMock);

        $resolverCall = 0;
        $this->parentVariantResolverMock->expects($this->exactly(2))
            ->method('resolveParentProductForRow')
            ->willReturnCallback(function (array $row, Product $product) use (
                &$resolverCall,
                $products,
                $productMock,
                $firstParentMock,
                $secondParentMock
            ) {
                $this->assertSame($productMock, $product);
                $this->assertSame($products[$resolverCall], $row);

                return $resolverCall++ === 0 ? $firstParentMock : $secondParentMock;
            });

        $pricesCall = 0;
        $priceProviderMock->expects($this->exactly(2))
            ->method('getPrices')
            ->willReturnCallback(function (Product $product, array $ignoredFields, ?Product $resolvedParent) use (
                &$pricesCall,
                $productMock,
                $firstParentMock,
                $secondParentMock
            ) {
                $this->assertSame($productMock, $product);
                $this->assertSame([], $ignoredFields);

                if ($pricesCall++ === 0) {
                    $this->assertSame($firstParentMock, $resolvedParent);

                    return ['regular_price' => 1.0, 'final_price' => 2.0, 'max_price' => 3.0];
                }

                $this->assertSame($secondParentMock, $resolvedParent);

                return ['regular_price' => 10.0, 'final_price' => 20.0, 'max_price' => 30.0];
            });

        $result = $this->pricesProvider->getData($products, $feedSpecificationMock);

        $this->assertSame(3.0, $result[0]['max_price']);
        $this->assertSame(30.0, $result[1]['max_price']);
    }
}
