<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\DataProvider as ConfigurableDataProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\DataProvider\StandardOptionsProvider;
use AthosCommerce\Feed\Service\Provider\StoreProvider;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StandardOptionsProviderTest extends TestCase
{
    private WriterInterface&MockObject $configWriterMock;

    private StoreProvider&MockObject $storeProviderMock;

    private Json&MockObject $jsonMock;

    private ParentVariantResolver&MockObject $parentVariantResolverMock;

    private ProductRepositoryInterface&MockObject $productRepositoryMock;

    private StandardOptionsProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configWriterMock = $this->createMock(WriterInterface::class);
        $this->storeProviderMock = $this->createMock(StoreProvider::class);
        $this->jsonMock = $this->createMock(Json::class);
        $this->parentVariantResolverMock = $this->createMock(ParentVariantResolver::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);

        $this->provider = new StandardOptionsProvider(
            $this->createMock(ConfigurableDataProvider::class),
            $this->createMock(AthosCommerceLogger::class),
            $this->configWriterMock,
            $this->storeProviderMock,
            $this->jsonMock,
            $this->parentVariantResolverMock,
            $this->productRepositoryMock
        );
    }

    public function testGetDataReturnsEmptyOptionsForStandaloneRows(): void
    {
        $productModelMock = $this->createMock(Product::class);
        $productModelMock->method('getTypeId')->willReturn('simple');

        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);

        $this->parentVariantResolverMock->expects($this->never())
            ->method('resolveParentProductForRow');
        $this->productRepositoryMock->expects($this->never())
            ->method('getById');
        $this->configWriterMock->expects($this->never())
            ->method('save');

        $result = $this->provider->getData([[
            'product_model' => $productModelMock,
            Constant::IS_STANDALONE_PRODUCT_KEY => true,
        ]], $feedSpecificationMock);

        $this->assertSame([], $result[0][StandardOptionsProvider::FIELD_KEY_STANDARD_OPTIONS]);
    }

    public function testGetDataAddsStandardOptionsForParentAwareRows(): void
    {
        $productModelMock = $this->createMock(Product::class);
        $productModelMock->method('getTypeId')->willReturn('simple');
        $productModelMock->method('getId')->willReturn(101);
        $productModelMock->method('getAttributeText')->willReturnMap([
            ['athos_color', 'Red'],
            ['athos_size', 'M'],
        ]);

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
                        'attribute_code' => 'athos_color',
                        'store_label' => 'Athos Color Label',
                    ]),
                ]),
                new DataObject([
                    'product_attribute' => new DataObject([
                        'attribute_code' => 'athos_size',
                        'store_label' => 'Size',
                    ]),
                ]),
            ]);

        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->method('getId')->willReturn(1);

        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getStoreCode')->willReturn('default');

        $serializedOptionNames = '{"Athos Color Label":"Athos Color Label","Size":"Size"}';

        $row = [
            'product_model' => $productModelMock,
            Constant::IS_STANDALONE_PRODUCT_KEY => false,
        ];

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->with($row, $productModelMock)
            ->willReturn($parentProductMock);

        $this->storeProviderMock->expects($this->once())
            ->method('getStore')
            ->with('default')
            ->willReturn($storeMock);

        $this->jsonMock->expects($this->once())
            ->method('serialize')
            ->with([
                'Athos Color Label' => 'Athos Color Label',
                'Size' => 'Size',
            ])
            ->willReturn($serializedOptionNames);

        $this->configWriterMock->expects($this->once())
            ->method('save')
            ->with(
                Constants::XML_PATH_ATTRIBUTE_VARIANT_OPTIONS_LIST,
                $serializedOptionNames,
                'stores',
                1
            );

        $result = $this->provider->getData([$row], $feedSpecificationMock);

        $this->assertSame(
            [
                'athos_color' => [
                    'label' => 'Athos Color Label',
                    'value' => 'Red',
                ],
                'athos_size' => [
                    'label' => 'Size',
                    'value' => 'M',
                ],
            ],
            $result[0][StandardOptionsProvider::FIELD_KEY_STANDARD_OPTIONS]
        );
    }

    public function testGetDataCachesResolvedParentAndOptionsForRepeatedRows(): void
    {
        $productModelMock = $this->createMock(Product::class);
        $productModelMock->method('getTypeId')->willReturn('simple');
        $productModelMock->method('getId')->willReturn(101);
        $productModelMock->method('getAttributeText')->willReturn('Red');

        $parentProductMock = $this->createMock(Product::class);
        $parentProductMock->method('getId')->willReturn(501);
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
                        'attribute_code' => 'athos_color',
                        'store_label' => 'Athos Color Label',
                    ]),
                ]),
            ]);

        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->method('getId')->willReturn(1);

        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getStoreCode')->willReturn('default');

        $this->storeProviderMock->expects($this->once())
            ->method('getStore')
            ->with('default')
            ->willReturn($storeMock);

        $this->jsonMock->expects($this->once())
            ->method('serialize')
            ->willReturn('{"Athos Color Label":"Athos Color Label"}');

        $this->configWriterMock->expects($this->once())
            ->method('save');

        $row = [
            'product_model' => $productModelMock,
            Constant::IS_STANDALONE_PRODUCT_KEY => false,
        ];

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->with($row, $productModelMock)
            ->willReturn($parentProductMock);

        $result = $this->provider->getData([$row, $row], $feedSpecificationMock);

        $this->assertSame(
            $result[0][StandardOptionsProvider::FIELD_KEY_STANDARD_OPTIONS],
            $result[1][StandardOptionsProvider::FIELD_KEY_STANDARD_OPTIONS]
        );
    }

    public function testGetDataUsesRowSpecificParentForSameSimpleProduct(): void
    {
        $productModelMock = $this->createMock(Product::class);
        $productModelMock->method('getTypeId')->willReturn('simple');
        $productModelMock->method('getId')->willReturn(101);
        $productModelMock->method('getAttributeText')->willReturnMap([
            ['athos_color', 'Red'],
            ['athos_size', 'M'],
        ]);

        $firstParentProductMock = $this->createMock(Product::class);
        $firstParentProductMock->method('getId')->willReturn(501);
        $firstParentProductMock->method('getTypeId')->willReturn(Constant::CONFIGURABLE_TYPE);
        $firstTypeInstanceMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getConfigurableAttributes'])
            ->getMock();
        $firstParentProductMock->method('getTypeInstance')->willReturn($firstTypeInstanceMock);

        $secondParentProductMock = $this->createMock(Product::class);
        $secondParentProductMock->method('getId')->willReturn(502);
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
                        'attribute_code' => 'athos_color',
                        'store_label' => 'Athos Color Label',
                    ]),
                ]),
            ]);

        $secondTypeInstanceMock->expects($this->once())
            ->method('getConfigurableAttributes')
            ->with($secondParentProductMock)
            ->willReturn([
                new DataObject([
                    'product_attribute' => new DataObject([
                        'attribute_code' => 'athos_size',
                        'store_label' => 'Size',
                    ]),
                ]),
            ]);

        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->method('getId')->willReturn(1);

        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getStoreCode')->willReturn('default');

        $this->storeProviderMock->expects($this->once())
            ->method('getStore')
            ->with('default')
            ->willReturn($storeMock);

        $this->jsonMock->expects($this->once())
            ->method('serialize')
            ->with([
                'Athos Color Label' => 'Athos Color Label',
                'Size' => 'Size',
            ])
            ->willReturn('{"Athos Color Label":"Athos Color Label","Size":"Size"}');

        $this->configWriterMock->expects($this->once())
            ->method('save');

        $rows = [
            [
                'product_model' => $productModelMock,
                Constant::IS_STANDALONE_PRODUCT_KEY => false,
                Constant::RESOLVED_PARENT_ID_KEY => 501,
                Constant::RESOLVED_PARENT_SKU_KEY => 'parent-one',
            ],
            [
                'product_model' => $productModelMock,
                Constant::IS_STANDALONE_PRODUCT_KEY => false,
                Constant::RESOLVED_PARENT_ID_KEY => 502,
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
                'athos_color' => [
                    'label' => 'Athos Color Label',
                    'value' => 'Red',
                ],
            ],
            $result[0][StandardOptionsProvider::FIELD_KEY_STANDARD_OPTIONS]
        );
        $this->assertSame(
            [
                'athos_size' => [
                    'label' => 'Size',
                    'value' => 'M',
                ],
            ],
            $result[1][StandardOptionsProvider::FIELD_KEY_STANDARD_OPTIONS]
        );
    }
}
