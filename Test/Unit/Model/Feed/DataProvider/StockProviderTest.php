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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\Context\StoreContextManager;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\StockProviderInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\StockResolverInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\StockProvider;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;

class StockProviderTest extends TestCase
{
    /**
     * @var StockResolverInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $stockResolverMock;

    /**
     * @var ParentVariantResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    private $parentVariantResolverMock;
    private $storeContextManagerMock;
    private $loggerMock;

    /**
     * @var StockProvider
     */
    private $stockProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockResolverMock = $this->createMock(StockResolverInterface::class);
        $this->parentVariantResolverMock = $this->createMock(ParentVariantResolver::class);
        $this->storeContextManagerMock = $this->createMock(StoreContextManager::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $storeMock = $this->createMock(Store::class);
        $storeMock->method('getId')->willReturn(1);
        $this->storeContextManagerMock->method('getStoreFromContext')->willReturn($storeMock);

        $this->stockProvider = new StockProvider(
            $this->stockResolverMock,
            $this->storeContextManagerMock,
            $this->parentVariantResolverMock,
            $this->loggerMock
        );
    }

    public function testGetDataAddsChildStockFieldsForStandaloneRow(): void
    {
        $providerMock = $this->createMock(StockProviderInterface::class);
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);

        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn([]);

        $feedSpecificationMock->expects($this->once())
            ->method('getIsMsiEnabled')
            ->willReturn(false);

        $products = [
            [
                'entity_id' => 1,
            ]
        ];

        $stockData = [
            1 => [
                'in_stock' => 1,
                'qty' => 333,
                'is_stock_managed' => 1,
            ]
        ];

        $this->stockResolverMock->expects($this->once())
            ->method('resolve')
            ->with(false)
            ->willReturn($providerMock);

        $providerMock->expects($this->once())
            ->method('getStock')
            ->with([1])
            ->willReturn($stockData);

        $this->assertSame(
            [[
                'entity_id' => 1,
                '__in_stock' => true,
                'in_stock' => 1,
                'stock_qty' => 333.0,
                'is_stock_managed' => 1,
            ]],
            $this->stockProvider->getData($products, $feedSpecificationMock)
        );
    }

    public function testGetDataAddsParentStockFieldsForResolvedParentContextRow(): void
    {
        $providerMock = $this->createMock(StockProviderInterface::class);
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $productModelMock = $this->createMock(Product::class);
        $parentProductMock = $this->createMock(Product::class);

        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn([]);

        $feedSpecificationMock->expects($this->once())
            ->method('getIsMsiEnabled')
            ->willReturn(false);

        $products = [[
            'entity_id' => 1,
            'product_model' => $productModelMock,
            '__is_belong_to_parent' => true,
        ]];

        $parentProductMock->expects($this->once())
            ->method('getId')
            ->willReturn(10);

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->with($products[0], $productModelMock)
            ->willReturn($parentProductMock);

        $this->stockResolverMock->expects($this->once())
            ->method('resolve')
            ->with(false)
            ->willReturn($providerMock);

        $providerMock->expects($this->exactly(2))
            ->method('getStock')
            ->willReturnMap([
                [
                    [1],
                    [
                        1 => [
                            'in_stock' => 1,
                            'qty' => 5,
                            'is_stock_managed' => 1,
                        ]
                    ]
                ],
                [
                    [10],
                    [
                        10 => [
                            'in_stock' => 1,
                            'qty' => 22,
                            'is_stock_managed' => 1,
                        ]
                    ]
                ],
            ]);

        $result = $this->stockProvider->getData($products, $feedSpecificationMock);

        $this->assertSame([[
            'entity_id' => 1,
            'product_model' => $productModelMock,
            '__is_belong_to_parent' => true,
            '__in_stock' => true,
            'in_stock' => 1,
            'stock_qty' => 5.0,
            'is_stock_managed' => 1,
            'parent_in_stock' => 1,
            'parent_stock_qty' => 22.0,
            'parent_is_stock_managed' => 1,
        ]], $result);
    }

    public function testGetDataResolvesParentOnlyOncePerRepeatedChildRow(): void
    {
        $providerMock = $this->createMock(StockProviderInterface::class);
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $productModelMock = $this->createMock(Product::class);
        $parentProductMock = $this->createMock(Product::class);

        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);
        $feedSpecificationMock->method('getIsMsiEnabled')->willReturn(false);

        $products = [
            ['entity_id' => 1, 'product_model' => $productModelMock],
            ['entity_id' => 1, 'product_model' => $productModelMock],
        ];

        $productModelMock->method('getId')->willReturn(1);
        $parentProductMock->method('getId')->willReturn(10);

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->with($products[0], $productModelMock)
            ->willReturn($parentProductMock);

        $this->stockResolverMock->expects($this->once())
            ->method('resolve')
            ->with(false)
            ->willReturn($providerMock);

        $providerMock->expects($this->exactly(2))
            ->method('getStock')
            ->willReturnMap([
                [[1], [1 => ['in_stock' => 1, 'qty' => 5, 'is_stock_managed' => 1]]],
                [[10], [10 => ['in_stock' => 1, 'qty' => 22, 'is_stock_managed' => 1]]],
            ]);

        $result = $this->stockProvider->getData($products, $feedSpecificationMock);

        $this->assertSame(22.0, $result[0]['parent_stock_qty']);
        $this->assertSame(22.0, $result[1]['parent_stock_qty']);
    }

    public function testGetDataUsesRowSpecificParentStockForSameChildProduct(): void
    {
        $providerMock = $this->createMock(StockProviderInterface::class);
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $productModelMock = $this->createMock(Product::class);
        $firstParentProductMock = $this->createMock(Product::class);
        $secondParentProductMock = $this->createMock(Product::class);

        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);
        $feedSpecificationMock->method('getIsMsiEnabled')->willReturn(false);

        $products = [
            [
                'entity_id' => 1,
                'product_model' => $productModelMock,
                Constant::RESOLVED_PARENT_ID_KEY => 10,
                Constant::RESOLVED_PARENT_SKU_KEY => 'parent-one',
            ],
            [
                'entity_id' => 1,
                'product_model' => $productModelMock,
                Constant::RESOLVED_PARENT_ID_KEY => 11,
                Constant::RESOLVED_PARENT_SKU_KEY => 'parent-two',
            ],
        ];

        $productModelMock->method('getId')->willReturn(1);
        $firstParentProductMock->method('getId')->willReturn(10);
        $secondParentProductMock->method('getId')->willReturn(11);

        $resolverCall = 0;
        $this->parentVariantResolverMock->expects($this->exactly(2))
            ->method('resolveParentProductForRow')
            ->willReturnCallback(function (array $row, Product $product) use (
                &$resolverCall,
                $products,
                $productModelMock,
                $firstParentProductMock,
                $secondParentProductMock
            ) {
                $this->assertSame($productModelMock, $product);
                $this->assertSame($products[$resolverCall], $row);

                return $resolverCall++ === 0 ? $firstParentProductMock : $secondParentProductMock;
            });

        $this->stockResolverMock->expects($this->once())
            ->method('resolve')
            ->with(false)
            ->willReturn($providerMock);

        $providerMock->expects($this->exactly(2))
            ->method('getStock')
            ->willReturnCallback(function (array $ids) {
                if ($ids === [1]) {
                    return [1 => ['in_stock' => 1, 'qty' => 5, 'is_stock_managed' => 1]];
                }

                $this->assertSame([10, 11], $ids);

                return [
                    10 => ['in_stock' => 1, 'qty' => 22, 'is_stock_managed' => 1],
                    11 => ['in_stock' => 1, 'qty' => 44, 'is_stock_managed' => 1],
                ];
            });

        $result = $this->stockProvider->getData($products, $feedSpecificationMock);

        $this->assertSame(22.0, $result[0]['parent_stock_qty']);
        $this->assertSame(44.0, $result[1]['parent_stock_qty']);
    }
}
