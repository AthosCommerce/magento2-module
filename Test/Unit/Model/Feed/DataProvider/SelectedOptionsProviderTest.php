<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentRelationsContext;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\SelectedOptionsProvider;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Helper\Data as ConfigurableHelper;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SelectedOptionsProviderTest extends TestCase
{
    private ConfigurableHelper&MockObject $configurableHelperMock;

    private ConfigurableAttributeData&MockObject $configurableAttributeDataMock;

    private ConfigurableType&MockObject $configurableTypeMock;

    private ParentRelationsContext&MockObject $parentRelationsContextMock;

    private SelectedOptionsProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurableHelperMock = $this->createMock(ConfigurableHelper::class);
        $this->configurableAttributeDataMock = $this->createMock(ConfigurableAttributeData::class);
        $this->configurableTypeMock = $this->createMock(ConfigurableType::class);
        $this->parentRelationsContextMock = $this->createMock(ParentRelationsContext::class);

        $this->provider = new SelectedOptionsProvider(
            $this->configurableHelperMock,
            $this->configurableAttributeDataMock,
            $this->configurableTypeMock,
            $this->createMock(AthosCommerceLogger::class),
            $this->parentRelationsContextMock
        );
    }

    public function testGetDataReturnsNullForStandaloneRows(): void
    {
        $simpleProductMock = $this->createMock(Product::class);
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);

        $this->parentRelationsContextMock->expects($this->never())
            ->method('getParentsByChildId');

        $result = $this->provider->getData([[
            'product_model' => $simpleProductMock,
            Constant::IS_STANDALONE_PRODUCT_KEY => true,
        ]], $feedSpecificationMock);

        $this->assertNull($result[0][SelectedOptionsProvider::FIELD_KEY_SELECTED_OPTIONS]);
    }

    public function testGetDataSerializesSelectedOptionsForParentAwareRows(): void
    {
        $simpleProductMock = $this->createMock(Product::class);
        $simpleProductMock->method('getId')->willReturn(101);

        $parentProductMock = $this->createMock(Product::class);
        $parentProductMock->method('getId')->willReturn(501);
        $parentProductMock->method('getTypeId')->willReturn(ConfigurableType::TYPE_CODE);

        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);

        $options = [
            'index' => [
                101 => [
                    10 => 100,
                ],
            ],
        ];
        $attributesData = [
            'attributes' => [
                10 => [
                    'code' => 'athos_color',
                    'options' => [
                        ['id' => 100, 'label' => 'Red'],
                    ],
                ],
            ],
        ];

        $this->parentRelationsContextMock->expects($this->once())
            ->method('getParentsByChildId')
            ->with(101)
            ->willReturn($parentProductMock);

        $this->configurableTypeMock->expects($this->once())
            ->method('getUsedProducts')
            ->with($parentProductMock)
            ->willReturn([$simpleProductMock]);

        $this->configurableHelperMock->expects($this->once())
            ->method('getOptions')
            ->with($parentProductMock, [$simpleProductMock])
            ->willReturn($options);

        $this->configurableAttributeDataMock->expects($this->once())
            ->method('getAttributesData')
            ->with($parentProductMock, $options)
            ->willReturn($attributesData);

        $result = $this->provider->getData([[
            'product_model' => $simpleProductMock,
            Constant::IS_STANDALONE_PRODUCT_KEY => false,
        ]], $feedSpecificationMock);

        $this->assertSame(
            json_encode(['athos_color' => ['value' => 'Red']]),
            $result[0][SelectedOptionsProvider::FIELD_KEY_SELECTED_OPTIONS]
        );
    }
}
