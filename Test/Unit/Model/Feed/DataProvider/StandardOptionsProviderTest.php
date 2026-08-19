<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\DataProvider as ConfigurableDataProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentDataContextManager;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentRelationsContext;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\StandardOptionsProvider;
use AthosCommerce\Feed\Service\Provider\StoreProvider;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StandardOptionsProviderTest extends TestCase
{
    private ParentDataContextManager&MockObject $parentDataContextManagerMock;

    private Configurable&MockObject $configurableTypeMock;

    private WriterInterface&MockObject $configWriterMock;

    private StoreProvider&MockObject $storeProviderMock;

    private Json&MockObject $jsonMock;

    private ParentRelationsContext&MockObject $parentRelationsContextMock;

    private ProductRepositoryInterface&MockObject $productRepositoryMock;

    private StandardOptionsProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parentDataContextManagerMock = $this->createMock(ParentDataContextManager::class);
        $this->configurableTypeMock = $this->createMock(Configurable::class);
        $this->configWriterMock = $this->createMock(WriterInterface::class);
        $this->storeProviderMock = $this->createMock(StoreProvider::class);
        $this->jsonMock = $this->createMock(Json::class);
        $this->parentRelationsContextMock = $this->createMock(ParentRelationsContext::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);

        $this->provider = new StandardOptionsProvider(
            $this->createMock(ConfigurableDataProvider::class),
            $this->createMock(AthosCommerceLogger::class),
            $this->parentDataContextManagerMock,
            $this->configurableTypeMock,
            $this->configWriterMock,
            $this->storeProviderMock,
            $this->jsonMock,
            $this->parentRelationsContextMock,
            $this->productRepositoryMock
        );
    }

    public function testGetDataReturnsEmptyOptionsForStandaloneRows(): void
    {
        $productModelMock = $this->createMock(Product::class);
        $productModelMock->method('getTypeId')->willReturn('simple');

        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);

        $this->configurableTypeMock->expects($this->never())
            ->method('getParentIdsByChild');
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

        $this->configurableTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with(101)
            ->willReturn([55]);

        $this->parentDataContextManagerMock->expects($this->once())
            ->method('getParentsDataByProductId')
            ->with(55)
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

        $result = $this->provider->getData([[
            'product_model' => $productModelMock,
            Constant::IS_STANDALONE_PRODUCT_KEY => false,
        ]], $feedSpecificationMock);

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
