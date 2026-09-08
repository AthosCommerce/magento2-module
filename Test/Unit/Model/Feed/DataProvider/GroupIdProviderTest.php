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
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentIdSourceFieldEvaluator;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GroupIdProviderTest extends TestCase
{
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
        $this->parentIdSourceFieldEvaluatorMock = $this->createMock(ParentIdSourceFieldEvaluator::class);

        $this->provider = new GroupIdProvider(
            $this->parentVariantResolverMock,
            $this->createMock(AthosCommerceLogger::class),
            $this->parentIdSourceFieldEvaluatorMock
        );
    }

    public function testGetDataReturnsProductsUnchangedWhenGroupIdIsIgnored(): void
    {
        $product = $this->createSimpleProductMock(101);
        $products = [[
            'product_model' => $product,
        ]];

        $feedSpecification = $this->createFeedSpecificationMock(null, null, [Constant::GROUP_ID]);

        $this->parentVariantResolverMock->expects($this->never())
            ->method('resolveParentProductForRow');
        $this->parentIdSourceFieldEvaluatorMock->expects($this->never())
            ->method('execute');

        $this->assertSame($products, $this->provider->getData($products, $feedSpecification));
    }

    public function testGetDataUsesMagentoParentIdWhenBothConfigurationFieldsAreBlank(): void
    {
        $childProduct = $this->createSimpleProductMock(101);
        $parentProduct = $this->createParentProductMock(501, Constant::CONFIGURABLE_TYPE);
        $products = [[
            'product_model' => $childProduct,
            Constant::IS_BELONG_TO_PARENT_KEY => true,
        ]];

        $feedSpecification = $this->createFeedSpecificationMock(null, null);

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

    public function testGetDataUsesParentBaseAndVariantValueForParentContextConfigurableRows(): void
    {
        $childProduct = $this->createSimpleProductMock(101);
        $parentProduct = $this->createParentProductMock(501, Constant::CONFIGURABLE_TYPE);
        $products = [[
            'product_model' => $childProduct,
            Constant::IS_BELONG_TO_PARENT_KEY => true,
        ]];

        $feedSpecification = $this->createFeedSpecificationMock(null, 'athos_color');

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
            ->with($parentProduct, null)
            ->willReturn('501');

        $result = $this->provider->getData($products, $feedSpecification);

        $this->assertSame('501::Red', $result[0][Constant::GROUP_ID]);
    }

    public function testGetDataUsesChildBaseForStandaloneConfigurableRowsWhenGroupingByVariantAttribute(): void
    {
        $childProduct = $this->createSimpleProductMock(101);
        $parentProduct = $this->createParentProductMock(501, Constant::CONFIGURABLE_TYPE);
        $products = [[
            'product_model' => $childProduct,
            Constant::IS_BELONG_TO_PARENT_KEY => false,
            Constant::IS_STANDALONE_PRODUCT_KEY => true,
        ]];

        $feedSpecification = $this->createFeedSpecificationMock(null, 'athos_color');

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

    public function testGetDataUsesConfiguredParentIdentifierWhenGroupByIsBlank(): void
    {
        $childProduct = $this->createSimpleProductMock(101);
        $parentProduct = $this->createParentProductMock(501, Constant::CONFIGURABLE_TYPE);
        $products = [[
            'product_model' => $childProduct,
            Constant::IS_BELONG_TO_PARENT_KEY => true,
        ]];

        $feedSpecification = $this->createFeedSpecificationMock('test_parent_group_code', null);

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
            ->with($parentProduct, 'test_parent_group_code')
            ->willReturn('TEST_PARENT_GROUP_001');

        $result = $this->provider->getData($products, $feedSpecification);

        $this->assertSame('TEST_PARENT_GROUP_001', $result[0][Constant::GROUP_ID]);
    }

    public function testGetDataUsesConfiguredParentIdentifierAndGenericMagentoAttribute(): void
    {
        $childProduct = $this->createSimpleProductMock(101, 'child-sku-101');
        $parentProduct = $this->createParentProductMock(501, Constant::CONFIGURABLE_TYPE);
        $products = [[
            'product_model' => $childProduct,
            Constant::IS_BELONG_TO_PARENT_KEY => true,
        ]];

        $feedSpecification = $this->createFeedSpecificationMock('test_parent_group_code', 'sku');

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
                if ($product->getId() === 501 && $identifier === 'test_parent_group_code') {
                    return 'TEST_PARENT_GROUP_001';
                }

                if ($product->getId() === 101 && $identifier === 'sku') {
                    return 'child-sku-101';
                }

                return null;
            });

        $result = $this->provider->getData($products, $feedSpecification);

        $this->assertSame('TEST_PARENT_GROUP_001::child-sku-101', $result[0][Constant::GROUP_ID]);
    }

    public function testGetDataUsesChildBaseForStandaloneConfigurableRowsWhenGroupingByGenericAttribute(): void
    {
        $childProduct = $this->createSimpleProductMock(101, 'child-sku-101');
        $parentProduct = $this->createParentProductMock(501, Constant::CONFIGURABLE_TYPE);
        $products = [[
            'product_model' => $childProduct,
            Constant::IS_BELONG_TO_PARENT_KEY => false,
            Constant::IS_STANDALONE_PRODUCT_KEY => true,
        ]];

        $feedSpecification = $this->createFeedSpecificationMock(null, 'sku');

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
        $products = [[
            'product_model' => $childProduct,
        ]];

        $feedSpecification = $this->createFeedSpecificationMock('test_parent_group_code', null);

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

    public function testGetDataUsesRowSpecificParentForSameChildProduct(): void
    {
        $childProduct = $this->createSimpleProductMock(101);
        $firstParentProduct = $this->createParentProductMock(501, Constant::CONFIGURABLE_TYPE);
        $secondParentProduct = $this->createParentProductMock(502, Constant::CONFIGURABLE_TYPE);
        $rows = [
            [
                'product_model' => $childProduct,
                Constant::IS_BELONG_TO_PARENT_KEY => true,
                Constant::RESOLVED_PARENT_ID_KEY => 501,
                Constant::RESOLVED_PARENT_SKU_KEY => 'parent-one',
            ],
            [
                'product_model' => $childProduct,
                Constant::IS_BELONG_TO_PARENT_KEY => true,
                Constant::RESOLVED_PARENT_ID_KEY => 502,
                Constant::RESOLVED_PARENT_SKU_KEY => 'parent-two',
            ],
        ];

        $feedSpecification = $this->createFeedSpecificationMock(null, 'athos_color');

        $resolverCall = 0;
        $this->parentVariantResolverMock->expects($this->exactly(2))
            ->method('resolveParentProductForRow')
            ->willReturnCallback(function (
                array $row,
                Product $product
            ) use (
                &$resolverCall,
                $rows,
                $childProduct,
                $firstParentProduct,
                $secondParentProduct
            ) {
                $this->assertSame($rows[$resolverCall], $row);
                $this->assertSame($childProduct, $product);

                return $resolverCall++ === 0 ? $firstParentProduct : $secondParentProduct;
            });

        $variantCall = 0;
        $this->parentVariantResolverMock->expects($this->exactly(2))
            ->method('getVariantOptions')
            ->willReturnCallback(function (
                Product $parentProduct,
                Product $productModel
            ) use (
                &$variantCall,
                $childProduct,
                $firstParentProduct,
                $secondParentProduct
            ): array {
                $this->assertSame($childProduct, $productModel);

                if ($variantCall++ === 0) {
                    $this->assertSame($firstParentProduct, $parentProduct);

                    return ['athos_color' => ['value' => 'Red']];
                }

                $this->assertSame($secondParentProduct, $parentProduct);

                return ['athos_color' => ['value' => 'Blue']];
            });

        $this->parentIdSourceFieldEvaluatorMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturnCallback(static function (Product $product, ?string $identifier): ?string {
                if ($identifier !== null) {
                    return null;
                }

                return (string)$product->getId();
            });

        $result = $this->provider->getData($rows, $feedSpecification);

        $this->assertSame('501::Red', $result[0][Constant::GROUP_ID]);
        $this->assertSame('502::Blue', $result[1][Constant::GROUP_ID]);
    }

    public function testResetClearsParentResolutionCache(): void
    {
        $childProduct = $this->createSimpleProductMock(101);
        $parentProduct = $this->createParentProductMock(501, Constant::CONFIGURABLE_TYPE);
        $products = [[
            'product_model' => $childProduct,
            Constant::IS_BELONG_TO_PARENT_KEY => true,
        ]];

        $feedSpecification = $this->createFeedSpecificationMock(null, null);

        $this->parentVariantResolverMock->expects($this->exactly(2))
            ->method('resolveParentProductForRow')
            ->with($products[0], $childProduct)
            ->willReturn($parentProduct);
        $this->parentVariantResolverMock->expects($this->exactly(2))
            ->method('getVariantOptions')
            ->with($parentProduct, $childProduct)
            ->willReturn([]);
        $this->parentIdSourceFieldEvaluatorMock->expects($this->exactly(2))
            ->method('execute')
            ->with($parentProduct, null)
            ->willReturn('501');

        $this->provider->getData($products, $feedSpecification);
        $this->provider->reset();
        $result = $this->provider->getData($products, $feedSpecification);

        $this->assertSame('501', $result[0][Constant::GROUP_ID]);
    }

    /**
     * @param string|null $parentIdSourceFieldName
     * @param string|null $groupBySourceFieldName
     * @param array $ignoreFields
     * @return FeedSpecificationInterface
     */
    private function createFeedSpecificationMock(
        ?string $parentIdSourceFieldName,
        ?string $groupBySourceFieldName,
        array $ignoreFields = []
    ): FeedSpecificationInterface {
        $feedSpecification = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecification->method('getIgnoreFields')->willReturn($ignoreFields);
        $feedSpecification->method('getParentIdSourceFieldName')->willReturn($parentIdSourceFieldName);
        $feedSpecification->method('getGroupBySourceFieldName')->willReturn($groupBySourceFieldName);

        return $feedSpecification;
    }

    /**
     * @param int $id
     * @param string $sku
     * @return Product
     */
    private function createSimpleProductMock(int $id, string $sku = 'test-simple-sku'): Product
    {
        return $this->createConfiguredMock(Product::class, [
            'getId' => $id,
            'getTypeId' => 'simple',
            'getSku' => $sku,
        ]);
    }

    /**
     * @param int $id
     * @param string $typeId
     * @return Product
     */
    private function createParentProductMock(int $id, string $typeId): Product
    {
        return $this->createConfiguredMock(Product::class, [
            'getId' => $id,
            'getTypeId' => $typeId,
        ]);
    }
}
