<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\GroupIdProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\TestCase;

class GroupIdProviderTest extends TestCase
{
    private ParentVariantResolver $parentVariantResolverMock;
    private AthosCommerceLogger $loggerMock;
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
    }
}
