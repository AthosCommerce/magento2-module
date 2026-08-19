<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\DataProvider as ConfigurableDataProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentDataContextManager;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentRelationsContext;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\SwatchOptionsProvider;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Swatches\Helper\Data as SwatchHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SwatchOptionsProviderTest extends TestCase
{
    private ParentDataContextManager&MockObject $parentDataContextManagerMock;

    private Configurable&MockObject $configurableTypeMock;

    private SwatchHelper&MockObject $swatchHelperMock;

    private StoreManagerInterface&MockObject $storeManagerMock;

    private ParentRelationsContext&MockObject $parentRelationsContextMock;

    private ProductRepositoryInterface&MockObject $productRepositoryMock;

    private SwatchOptionsProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parentDataContextManagerMock = $this->createMock(ParentDataContextManager::class);
        $this->configurableTypeMock = $this->createMock(Configurable::class);
        $this->swatchHelperMock = $this->createMock(SwatchHelper::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->parentRelationsContextMock = $this->createMock(ParentRelationsContext::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);

        $this->provider = new SwatchOptionsProvider(
            $this->createMock(ConfigurableDataProvider::class),
            $this->createMock(AthosCommerceLogger::class),
            $this->parentDataContextManagerMock,
            $this->configurableTypeMock,
            $this->swatchHelperMock,
            $this->storeManagerMock,
            $this->parentRelationsContextMock,
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

        $this->configurableTypeMock->expects($this->never())
            ->method('getParentIdsByChild');

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

        $this->configurableTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with(101)
            ->willReturn([55]);

        $this->parentDataContextManagerMock->expects($this->once())
            ->method('getParentsDataByProductId')
            ->with(55)
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

        $result = $this->provider->getData([[
            'product_model' => $productModelMock,
            Constant::IS_STANDALONE_PRODUCT_KEY => false,
        ]], $feedSpecificationMock);

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
}
