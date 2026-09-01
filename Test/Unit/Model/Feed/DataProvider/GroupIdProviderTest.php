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
}
