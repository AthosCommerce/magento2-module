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
}
