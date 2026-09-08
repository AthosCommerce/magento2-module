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
use AthosCommerce\Feed\Model\Feed\DataProvider\GroupIdProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use Magento\Catalog\Model\Product;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentIdSourceFieldEvaluator;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GroupIdProviderTest extends TestCase
{
    private ParentVariantResolver $parentVariantResolverMock;
    private AthosCommerceLogger $loggerMock;
    /**
     * @var ParentVariantResolver&MockObject
     */
    private $parentVariantResolverMock;

    /**
     * @var ParentIdSourceFieldEvaluator&MockObject
     */
    private $parentIdSourceFieldEvaluatorMock;

    /**
     * @var GroupIdProvider
     */
    private GroupIdProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parentVariantResolverMock = $this->createMock(ParentVariantResolver::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->provider = new GroupIdProvider(
            $this->parentVariantResolverMock,
            $this->loggerMock
        );
    }

    public function testGetDataCachesParentResolutionAndVariantOptionsForRepeatedRows(): void
    {
        $productModelMock = $this->createMock(Product::class);
        $productModelMock->method('getId')->willReturn(11);
        $productModelMock->method('getTypeId')->willReturn('simple');

        $parentProductMock = $this->createMock(Product::class);
        $parentProductMock->method('getId')->willReturn(100);
        $parentProductMock->method('getTypeId')->willReturn(Constant::CONFIGURABLE_TYPE);

        $row = [
            'product_model' => $productModelMock,
            Constant::IS_BELONG_TO_PARENT_KEY => true,
        ];

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);
        $feedSpecificationMock->method('getGroupBySourceFieldName')->willReturn('athos_color');

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->with($row, $productModelMock)
            ->willReturn($parentProductMock);

        $this->parentVariantResolverMock->expects($this->once())
            ->method('getVariantOptions')
            ->with($parentProductMock, $productModelMock)
        $this->parentIdSourceFieldEvaluatorMock = $this->createMock(ParentIdSourceFieldEvaluator::class);

        $this->provider = new GroupIdProvider(
            $this->parentVariantResolverMock,
            $this->createMock(AthosCommerceLogger::class),
            $this->parentIdSourceFieldEvaluatorMock
        );
    }

    public function testGetDataUsesMagentoParentIdWhenBothConfigurationFieldsAreBlank(): void
    {
        $childProduct = $this->createSimpleProductMock(101);
        $parentProduct = $this->createParentProductMock(501, Constant::CONFIGURABLE_TYPE);
        $feedSpecification = $this->createFeedSpecificationMock(null, null);
        $products = [[
            'product_model' => $childProduct,
            Constant::IS_BELONG_TO_PARENT_KEY => true,
        ]];

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->with($products[0], $childProduct)
            ->willReturn($parentProduct);
        $this->parentVariantResolverMock->expects($this->once())
            ->method('getVariantOptions')
            ->with($parentProduct, $childProduct)
            ->willReturn([]);

        $this->parentIdSourceFieldEvaluatorMock->expects($this->once())
            ->method('execute')
            ->with($parentProduct, null)
            ->willReturn('501');

        $result = $this->provider->getData($products, $feedSpecification);

        $this->assertSame('501', $result[0][Constant::GROUP_ID]);
    }

    public function testGetDataUsesMagentoParentIdAndOptionValueWhenGroupingByVariantAttribute(): void
    {
        $childProduct = $this->createSimpleProductMock(101);
        $parentProduct = $this->createParentProductMock(501, Constant::CONFIGURABLE_TYPE);
        $feedSpecification = $this->createFeedSpecificationMock(null, 'athos_color');
        $products = [[
            'product_model' => $childProduct,
            Constant::IS_BELONG_TO_PARENT_KEY => false,
        ]];

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->with($products[0], $childProduct)
            ->willReturn($parentProduct);
        $this->parentVariantResolverMock->expects($this->once())
            ->method('getVariantOptions')
            ->with($parentProduct, $childProduct)
            ->willReturn([
                'athos_color' => ['value' => 'Red'],
            ]);

        $result = $this->provider->getData([$row, $row], $feedSpecificationMock);

        $this->assertSame('100::Red', $result[0]['__group_id']);
        $this->assertSame('100::Red', $result[1]['__group_id']);
    }

    public function testGetDataUsesRowSpecificParentForSameChildProduct(): void
    {
        $productModelMock = $this->createMock(Product::class);
        $productModelMock->method('getId')->willReturn(11);
        $productModelMock->method('getTypeId')->willReturn('simple');

        $firstParentProductMock = $this->createMock(Product::class);
        $firstParentProductMock->method('getId')->willReturn(100);
        $firstParentProductMock->method('getTypeId')->willReturn(Constant::CONFIGURABLE_TYPE);

        $secondParentProductMock = $this->createMock(Product::class);
        $secondParentProductMock->method('getId')->willReturn(101);
        $secondParentProductMock->method('getTypeId')->willReturn(Constant::CONFIGURABLE_TYPE);

        $rows = [
            [
                'product_model' => $productModelMock,
                Constant::IS_BELONG_TO_PARENT_KEY => true,
                Constant::RESOLVED_PARENT_ID_KEY => 100,
                Constant::RESOLVED_PARENT_SKU_KEY => 'parent-one',
            ],
            [
                'product_model' => $productModelMock,
                Constant::IS_BELONG_TO_PARENT_KEY => true,
                Constant::RESOLVED_PARENT_ID_KEY => 101,
                Constant::RESOLVED_PARENT_SKU_KEY => 'parent-two',
            ],
        ];

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);
        $feedSpecificationMock->method('getGroupBySourceFieldName')->willReturn('athos_color');

        $resolverCall = 0;
        $this->parentVariantResolverMock->expects($this->exactly(2))
            ->method('resolveParentProductForRow')
            ->willReturnCallback(function (array $row, Product $product) use (
                &$resolverCall,
                $rows,
                $productModelMock,
                $firstParentProductMock,
                $secondParentProductMock
            ) {
                $this->assertSame($productModelMock, $product);
                $this->assertSame($rows[$resolverCall], $row);

                return $resolverCall++ === 0 ? $firstParentProductMock : $secondParentProductMock;
            });

        $variantCall = 0;
        $this->parentVariantResolverMock->expects($this->exactly(2))
            ->method('getVariantOptions')
            ->willReturnCallback(function (Product $parentProduct, Product $productModel) use (
                &$variantCall,
                $productModelMock,
                $firstParentProductMock,
                $secondParentProductMock
            ) {
                $this->assertSame($productModelMock, $productModel);

                if ($variantCall++ === 0) {
                    $this->assertSame($firstParentProductMock, $parentProduct);

                    return ['athos_color' => ['value' => 'Red']];
                }

                $this->assertSame($secondParentProductMock, $parentProduct);

                return ['athos_color' => ['value' => 'Blue']];
            });

        $result = $this->provider->getData($rows, $feedSpecificationMock);

        $this->assertSame('100::Red', $result[0]['__group_id']);
        $this->assertSame('101::Blue', $result[1]['__group_id']);
        $this->parentIdSourceFieldEvaluatorMock->expects($this->once())
            ->method('execute')
            ->with($parentProduct, null)
            ->willReturn('501');

        $result = $this->provider->getData($products, $feedSpecification);

        $this->assertSame('501::Red', $result[0][Constant::GROUP_ID]);
    }

    public function testGetDataUsesConfiguredParentIdentifierWhenGroupByIsBlank(): void
    {
        $childProduct = $this->createSimpleProductMock(101);
        $parentProduct = $this->createParentProductMock(501, Constant::CONFIGURABLE_TYPE);
        $feedSpecification = $this->createFeedSpecificationMock('test_parent_group_code', null);
        $products = [
            [
                'product_model' => $childProduct,
                Constant::IS_BELONG_TO_PARENT_KEY => false,
            ],
            [
                'product_model' => $childProduct,
                Constant::IS_BELONG_TO_PARENT_KEY => true,
            ],
        ];

        $this->parentVariantResolverMock->expects($this->exactly(2))
            ->method('resolveParentProductForRow')
            ->willReturn($parentProduct);
        $this->parentVariantResolverMock->expects($this->exactly(2))
            ->method('getVariantOptions')
            ->with($parentProduct, $childProduct)
            ->willReturn([]);

        $this->parentIdSourceFieldEvaluatorMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturnCallback(static function (Product $product, ?string $identifier): string {
                if ($identifier !== 'test_parent_group_code') {
                    return '';
                }

                return $product->getId() === 501
                    ? 'TEST_PARENT_GROUP_001'
                    : 'TEST_PARENT_GROUP_001';
            });

        $result = $this->provider->getData($products, $feedSpecification);

        $this->assertSame('TEST_PARENT_GROUP_001', $result[0][Constant::GROUP_ID]);
        $this->assertSame('TEST_PARENT_GROUP_001', $result[1][Constant::GROUP_ID]);
    }

    public function testGetDataUsesConfiguredParentIdentifierPrefixWhenBothConfigurationFieldsAreSet(): void
    {
        $childProduct = $this->createSimpleProductMock(101);
        $parentProduct = $this->createParentProductMock(501, Constant::CONFIGURABLE_TYPE);
        $feedSpecification = $this->createFeedSpecificationMock(
            'test_parent_group_code',
            'athos_color'
        );
        $products = [[
            'product_model' => $childProduct,
            Constant::IS_BELONG_TO_PARENT_KEY => true,
        ]];

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->with($products[0], $childProduct)
            ->willReturn($parentProduct);
        $this->parentVariantResolverMock->expects($this->once())
            ->method('getVariantOptions')
            ->with($parentProduct, $childProduct)
            ->willReturn([
                'athos_color' => ['value' => 'Blue'],
            ]);

        $this->parentIdSourceFieldEvaluatorMock->expects($this->once())
            ->method('execute')
            ->with($parentProduct, 'test_parent_group_code')
            ->willReturn('TEST_PARENT_GROUP_001');

        $result = $this->provider->getData($products, $feedSpecification);

        $this->assertSame('TEST_PARENT_GROUP_001::Blue', $result[0][Constant::GROUP_ID]);
    }

    public function testGetDataUsesChildBaseForStandaloneConfigurableRowsWhenGroupingByVariantAttribute(): void
    {
        $childProduct = $this->createSimpleProductMock(101);
        $parentProduct = $this->createParentProductMock(501, Constant::CONFIGURABLE_TYPE);
        $feedSpecification = $this->createFeedSpecificationMock(null, 'athos_color');
        $products = [[
            'product_model' => $childProduct,
            Constant::IS_BELONG_TO_PARENT_KEY => false,
            Constant::IS_STANDALONE_PRODUCT_KEY => true,
        ]];

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->with($products[0], $childProduct)
            ->willReturn($parentProduct);
        $this->parentVariantResolverMock->expects($this->once())
            ->method('getVariantOptions')
            ->with($parentProduct, $childProduct)
            ->willReturn([
                'athos_color' => ['value' => 'Red'],
            ]);

        $this->parentIdSourceFieldEvaluatorMock->expects($this->once())
            ->method('execute')
            ->with($childProduct, null)
            ->willReturn('101');

        $result = $this->provider->getData($products, $feedSpecification);

        $this->assertSame('101::Red', $result[0][Constant::GROUP_ID]);
    }

    public function testGetDataUsesGenericMagentoAttributeWhenGroupByIsNotAVariantOption(): void
    {
        $childProduct = $this->createSimpleProductMock(101, 'child-sku-101');
        $parentProduct = $this->createParentProductMock(501, Constant::CONFIGURABLE_TYPE);
        $feedSpecification = $this->createFeedSpecificationMock(null, 'sku');
        $products = [[
            'product_model' => $childProduct,
            Constant::IS_BELONG_TO_PARENT_KEY => true,
        ]];

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->with($products[0], $childProduct)
            ->willReturn($parentProduct);
        $this->parentVariantResolverMock->expects($this->once())
            ->method('getVariantOptions')
            ->with($parentProduct, $childProduct)
            ->willReturn([]);

        $this->parentIdSourceFieldEvaluatorMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturnCallback(static function (Product $product, ?string $identifier): ?string {
                if ($product->getId() === 501 && $identifier === null) {
                    return '501';
                }

                if ($product->getId() === 101 && $identifier === 'sku') {
                    return 'child-sku-101';
                }

                return null;
            });

        $result = $this->provider->getData($products, $feedSpecification);

        $this->assertSame('501::child-sku-101', $result[0][Constant::GROUP_ID]);
    }

    public function testGetDataUsesChildBaseForStandaloneConfigurableRowsWhenGroupingByGenericAttribute(): void
    {
        $childProduct = $this->createSimpleProductMock(101, 'child-sku-101');
        $parentProduct = $this->createParentProductMock(501, Constant::CONFIGURABLE_TYPE);
        $feedSpecification = $this->createFeedSpecificationMock(null, 'sku');
        $products = [[
            'product_model' => $childProduct,
            Constant::IS_BELONG_TO_PARENT_KEY => false,
            Constant::IS_STANDALONE_PRODUCT_KEY => true,
        ]];

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->with($products[0], $childProduct)
            ->willReturn($parentProduct);
        $this->parentVariantResolverMock->expects($this->once())
            ->method('getVariantOptions')
            ->with($parentProduct, $childProduct)
            ->willReturn([]);

        $this->parentIdSourceFieldEvaluatorMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturnCallback(static function (Product $product, ?string $identifier): ?string {
                if ($product->getId() === 101 && $identifier === null) {
                    return '101';
                }

                if ($product->getId() === 101 && $identifier === 'sku') {
                    return 'child-sku-101';
                }

                return null;
            });

        $result = $this->provider->getData($products, $feedSpecification);

        $this->assertSame('101::child-sku-101', $result[0][Constant::GROUP_ID]);
    }

    public function testGetDataFallsBackToProductIdWhenConfiguredIdentifierIsMissing(): void
    {
        $childProduct = $this->createSimpleProductMock(101);
        $feedSpecification = $this->createFeedSpecificationMock('test_parent_group_code', null);
        $products = [[
            'product_model' => $childProduct,
        ]];

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->with($products[0], $childProduct)
            ->willReturn(null);

        $this->parentIdSourceFieldEvaluatorMock->expects($this->once())
            ->method('execute')
            ->with($childProduct, 'test_parent_group_code')
            ->willReturn(null);

        $result = $this->provider->getData($products, $feedSpecification);

        $this->assertSame('101', $result[0][Constant::GROUP_ID]);
    }

    public function testGetDataUsesGenericMagentoAttributeForStandaloneRowsWhenGroupByIsConfigured(): void
    {
        $childProduct = $this->createSimpleProductMock(101, 'child-sku-101');
        $feedSpecification = $this->createFeedSpecificationMock(null, 'sku');
        $products = [[
            'product_model' => $childProduct,
        ]];

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->with($products[0], $childProduct)
            ->willReturn(null);

        $this->parentIdSourceFieldEvaluatorMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturnCallback(static function (Product $product, ?string $identifier): ?string {
                if ($product->getId() === 101 && $identifier === null) {
                    return '101';
                }

                if ($product->getId() === 101 && $identifier === 'sku') {
                    return 'child-sku-101';
                }

                return null;
            });

        $result = $this->provider->getData($products, $feedSpecification);

        $this->assertSame('101::child-sku-101', $result[0][Constant::GROUP_ID]);
    }

    private function createFeedSpecificationMock(
        ?string $parentIdSourceFieldName,
        ?string $groupBySourceFieldName
    ): FeedSpecificationInterface {
        $feedSpecification = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecification->method('getIgnoreFields')->willReturn([]);
        $feedSpecification->method('getParentIdSourceFieldName')->willReturn($parentIdSourceFieldName);
        $feedSpecification->method('getGroupBySourceFieldName')->willReturn($groupBySourceFieldName);

        return $feedSpecification;
    }

    private function createSimpleProductMock(int $id, string $sku = 'test-simple-sku'): Product
    {
        return $this->createConfiguredMock(Product::class, [
            'getId' => $id,
            'getTypeId' => 'simple',
            'getSku' => $sku,
        ]);
    }

    private function createParentProductMock(int $id, string $typeId): Product
    {
        return $this->createConfiguredMock(Product::class, [
            'getId' => $id,
            'getTypeId' => $typeId,
        ]);
    }
}
