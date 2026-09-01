<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\DataProvider as ConfigurableDataProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\DataProvider\SwatchOptionsProvider;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Swatches\Helper\Data as SwatchHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SwatchOptionsProviderTest extends TestCase
{
    private SwatchHelper&MockObject $swatchHelperMock;

    private StoreManagerInterface&MockObject $storeManagerMock;

    private ParentVariantResolver&MockObject $parentVariantResolverMock;

    private ProductRepositoryInterface&MockObject $productRepositoryMock;

    private SwatchOptionsProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->swatchHelperMock = $this->createMock(SwatchHelper::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->parentVariantResolverMock = $this->createMock(ParentVariantResolver::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);

        $this->provider = new SwatchOptionsProvider(
            $this->createMock(ConfigurableDataProvider::class),
            $this->createMock(AthosCommerceLogger::class),
            $this->swatchHelperMock,
            $this->storeManagerMock,
            $this->parentVariantResolverMock,
            $this->productRepositoryMock
        );
    }

    public function testGetDataReturnsEmptySwatchOptionsForStandaloneRows(): void
    {
        $productModelMock = $this->createMock(Product::class);
        $productModelMock->method('getTypeId')->willReturn('simple');

        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);
        $feedSpecificationMock->method('getSwatchOptionFieldsNames')->willReturn(['flavour_visual_swatch_attribute']);

        $this->parentVariantResolverMock->expects($this->never())
            ->method('resolveParentProductForRow');
        $this->productRepositoryMock->expects($this->never())
            ->method('getById');

        $result = $this->provider->getData([[
            'product_model' => $productModelMock,
            Constant::IS_STANDALONE_PRODUCT_KEY => true,
        ]], $feedSpecificationMock);

        $this->assertSame([], $result[0][SwatchOptionsProvider::FIELD_KEY]);
    }

    public function testGetDataAddsSwatchMetadataForParentAwareRows(): void
    {
        $productModelMock = $this->createMock(Product::class);
        $productModelMock->method('getTypeId')->willReturn('simple');
        $productModelMock->method('getId')->willReturn(101);
        $productModelMock->method('getAttributeText')->willReturnMap([
            ['flavour_visual_swatch_attribute', 'Option 1'],
        ]);
        $productModelMock->expects($this->once())
            ->method('getData')
            ->with('flavour_visual_swatch_attribute')
            ->willReturn(123);

        $parentProductMock = $this->createMock(Product::class);
        $parentProductMock->method('getTypeId')->willReturn(Constant::CONFIGURABLE_TYPE);
        $typeInstanceMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getConfigurableAttributes'])
            ->getMock();
        $parentProductMock->method('getTypeInstance')->willReturn($typeInstanceMock);

        $typeInstanceMock->expects($this->once())
            ->method('getConfigurableAttributes')
            ->with($parentProductMock)
            ->willReturn([
                new DataObject([
                    'product_attribute' => new DataObject([
                        'attribute_code' => 'flavour_visual_swatch_attribute',
                        'store_label' => 'Flavour Visual swatch attribute',
                        'default_value' => '',
                    ]),
                ]),
            ]);

        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->once())
            ->method('getBaseUrl')
            ->with(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
            ->willReturn('https://example.test/media/');

        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);
        $feedSpecificationMock->method('getSwatchOptionFieldsNames')->willReturn(['flavour_visual_swatch_attribute']);

        $row = [
            'product_model' => $productModelMock,
            Constant::IS_STANDALONE_PRODUCT_KEY => false,
        ];

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->with($row, $productModelMock)
            ->willReturn($parentProductMock);

        $this->swatchHelperMock->expects($this->once())
            ->method('getSwatchesByOptionsId')
            ->with([123])
            ->willReturn([
                123 => [
                    'value' => '#555555',
                    'thumbnail' => 'option-1.png',
                ],
            ]);

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $result = $this->provider->getData([$row], $feedSpecificationMock);

        $this->assertSame(
            [
                'flavour_visual_swatch_attribute' => [
                    'label' => 'Flavour Visual swatch attribute',
                    'value' => 'Option 1',
                    'default' => '',
                    'id' => 123,
                    'colors' => ['#555555'],
                    'image' => 'https://example.test/media/attribute/swatch/option-1.png',
                ],
            ],
            $result[0][SwatchOptionsProvider::FIELD_KEY]
        );
    }

    public function testGetDataCachesResolvedParentAndSwatchLookupForRepeatedRows(): void
    {
        $productModelMock = $this->createMock(Product::class);
        $productModelMock->method('getTypeId')->willReturn('simple');
        $productModelMock->method('getId')->willReturn(101);
        $productModelMock->method('getSku')->willReturn('sku-101');
        $productModelMock->method('getAttributeText')->willReturn('Option 1');
        $productModelMock->method('getData')->with('flavour_visual_swatch_attribute')->willReturn(123);

        $parentProductMock = $this->createMock(Product::class);
        $parentProductMock->method('getId')->willReturn(777);
        $parentProductMock->method('getTypeId')->willReturn(Constant::CONFIGURABLE_TYPE);
        $typeInstanceMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getConfigurableAttributes'])
            ->getMock();
        $parentProductMock->method('getTypeInstance')->willReturn($typeInstanceMock);

        $typeInstanceMock->expects($this->once())
            ->method('getConfigurableAttributes')
            ->with($parentProductMock)
            ->willReturn([
                new DataObject([
                    'product_attribute' => new DataObject([
                        'attribute_code' => 'flavour_visual_swatch_attribute',
                        'store_label' => 'Flavour Visual swatch attribute',
                        'default_value' => '',
                    ]),
                ]),
            ]);

        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->once())
            ->method('getBaseUrl')
            ->with(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
            ->willReturn('https://example.test/media/');

        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);
        $feedSpecificationMock->method('getSwatchOptionFieldsNames')->willReturn(['flavour_visual_swatch_attribute']);

        $row = [
            'product_model' => $productModelMock,
            Constant::IS_STANDALONE_PRODUCT_KEY => false,
        ];

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->with($row, $productModelMock)
            ->willReturn($parentProductMock);

        $this->swatchHelperMock->expects($this->once())
            ->method('getSwatchesByOptionsId')
            ->with([123])
            ->willReturn([
                123 => [
                    'value' => '#555555',
                    'thumbnail' => 'option-1.png',
                ],
            ]);

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $result = $this->provider->getData([$row, $row], $feedSpecificationMock);

        $this->assertSame($result[0][SwatchOptionsProvider::FIELD_KEY], $result[1][SwatchOptionsProvider::FIELD_KEY]);
    }

    public function testGetDataUsesRowSpecificParentForSameSimpleProduct(): void
    {
        $productModelMock = $this->createMock(Product::class);
        $productModelMock->method('getTypeId')->willReturn('simple');
        $productModelMock->method('getId')->willReturn(101);
        $productModelMock->method('getSku')->willReturn('sku-101');
        $productModelMock->method('getAttributeText')->willReturnMap([
            ['flavour_visual_swatch_attribute', 'Option 1'],
            ['size_swatch', 'Medium'],
        ]);
        $productModelMock->method('getData')->willReturnMap([
            ['flavour_visual_swatch_attribute', 123],
            ['size_swatch', 456],
        ]);

        $firstParentProductMock = $this->createMock(Product::class);
        $firstParentProductMock->method('getId')->willReturn(777);
        $firstParentProductMock->method('getTypeId')->willReturn(Constant::CONFIGURABLE_TYPE);
        $firstTypeInstanceMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getConfigurableAttributes'])
            ->getMock();
        $firstParentProductMock->method('getTypeInstance')->willReturn($firstTypeInstanceMock);

        $secondParentProductMock = $this->createMock(Product::class);
        $secondParentProductMock->method('getId')->willReturn(778);
        $secondParentProductMock->method('getTypeId')->willReturn(Constant::CONFIGURABLE_TYPE);
        $secondTypeInstanceMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getConfigurableAttributes'])
            ->getMock();
        $secondParentProductMock->method('getTypeInstance')->willReturn($secondTypeInstanceMock);

        $firstTypeInstanceMock->expects($this->once())
            ->method('getConfigurableAttributes')
            ->with($firstParentProductMock)
            ->willReturn([
                new DataObject([
                    'product_attribute' => new DataObject([
                        'attribute_code' => 'flavour_visual_swatch_attribute',
                        'store_label' => 'Flavour Visual swatch attribute',
                        'default_value' => '',
                    ]),
                ]),
            ]);

        $secondTypeInstanceMock->expects($this->once())
            ->method('getConfigurableAttributes')
            ->with($secondParentProductMock)
            ->willReturn([
                new DataObject([
                    'product_attribute' => new DataObject([
                        'attribute_code' => 'size_swatch',
                        'store_label' => 'Size swatch',
                        'default_value' => 'M',
                    ]),
                ]),
            ]);

        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);
        $feedSpecificationMock->method('getSwatchOptionFieldsNames')->willReturn([
            'flavour_visual_swatch_attribute',
            'size_swatch',
        ]);

        $rows = [
            [
                'product_model' => $productModelMock,
                Constant::IS_STANDALONE_PRODUCT_KEY => false,
                Constant::RESOLVED_PARENT_ID_KEY => 777,
                Constant::RESOLVED_PARENT_SKU_KEY => 'parent-one',
            ],
            [
                'product_model' => $productModelMock,
                Constant::IS_STANDALONE_PRODUCT_KEY => false,
                Constant::RESOLVED_PARENT_ID_KEY => 778,
                Constant::RESOLVED_PARENT_SKU_KEY => 'parent-two',
            ],
        ];

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

        $result = $this->provider->getData($rows, $feedSpecificationMock);

        $this->assertSame(
            [
                'flavour_visual_swatch_attribute' => [
                    'label' => 'Flavour Visual swatch attribute',
                    'value' => 'Option 1',
                    'default' => '',
                ],
            ],
            $result[0][SwatchOptionsProvider::FIELD_KEY]
        );
        $this->assertSame(
            [
                'size_swatch' => [
                    'label' => 'Size swatch',
                    'value' => 'Medium',
                    'default' => 'M',
                ],
            ],
            $result[1][SwatchOptionsProvider::FIELD_KEY]
        );
    }
}
