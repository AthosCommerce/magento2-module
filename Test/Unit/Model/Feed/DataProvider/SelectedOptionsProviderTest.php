<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
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

    private ParentVariantResolver&MockObject $parentVariantResolverMock;

    private SelectedOptionsProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurableHelperMock = $this->createMock(ConfigurableHelper::class);
        $this->configurableAttributeDataMock = $this->createMock(ConfigurableAttributeData::class);
        $this->configurableTypeMock = $this->createMock(ConfigurableType::class);
        $this->parentVariantResolverMock = $this->createMock(ParentVariantResolver::class);

        $this->provider = new SelectedOptionsProvider(
            $this->configurableHelperMock,
            $this->configurableAttributeDataMock,
            $this->configurableTypeMock,
            $this->createMock(AthosCommerceLogger::class),
            $this->parentVariantResolverMock
        );
    }

    public function testGetDataReturnsNullForStandaloneRows(): void
    {
        $simpleProductMock = $this->createMock(Product::class);
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);

        $this->parentVariantResolverMock->expects($this->never())
            ->method('resolveParentProductForRow');

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

        $row = [
            'product_model' => $simpleProductMock,
            Constant::IS_STANDALONE_PRODUCT_KEY => false,
        ];

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->with($row, $simpleProductMock)
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

        $result = $this->provider->getData([$row], $feedSpecificationMock);

        $this->assertSame(
            json_encode(['athos_color' => ['value' => 'Red']]),
            $result[0][SelectedOptionsProvider::FIELD_KEY_SELECTED_OPTIONS]
        );
    }

    public function testGetDataUsesRowSpecificParentForSameSimpleProduct(): void
    {
        $simpleProductMock = $this->createMock(Product::class);
        $simpleProductMock->method('getId')->willReturn(101);

        $firstParentMock = $this->createConfiguredMock(Product::class, [
            'getId' => 501,
            'getTypeId' => ConfigurableType::TYPE_CODE,
        ]);
        $secondParentMock = $this->createConfiguredMock(Product::class, [
            'getId' => 502,
            'getTypeId' => ConfigurableType::TYPE_CODE,
        ]);

        $rows = [
            [
                'product_model' => $simpleProductMock,
                Constant::IS_STANDALONE_PRODUCT_KEY => false,
                Constant::RESOLVED_PARENT_ID_KEY => 501,
                Constant::RESOLVED_PARENT_SKU_KEY => 'parent-one',
            ],
            [
                'product_model' => $simpleProductMock,
                Constant::IS_STANDALONE_PRODUCT_KEY => false,
                Constant::RESOLVED_PARENT_ID_KEY => 502,
                Constant::RESOLVED_PARENT_SKU_KEY => 'parent-two',
            ],
        ];

        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);

        $resolverCall = 0;
        $this->parentVariantResolverMock->expects($this->exactly(2))
            ->method('resolveParentProductForRow')
            ->willReturnCallback(function (array $row, Product $product) use (
                &$resolverCall,
                $rows,
                $simpleProductMock,
                $firstParentMock,
                $secondParentMock
            ) {
                $this->assertSame($simpleProductMock, $product);
                $this->assertSame($rows[$resolverCall], $row);

                return $resolverCall++ === 0 ? $firstParentMock : $secondParentMock;
            });

        $this->configurableTypeMock->expects($this->exactly(2))
            ->method('getUsedProducts')
            ->willReturn([]);

        $optionsCall = 0;
        $this->configurableHelperMock->expects($this->exactly(2))
            ->method('getOptions')
            ->willReturnCallback(function (Product $parentProduct, array $allowedProducts) use (
                &$optionsCall,
                $firstParentMock,
                $secondParentMock
            ) {
                $this->assertSame([], $allowedProducts);

                if ($optionsCall++ === 0) {
                    $this->assertSame($firstParentMock, $parentProduct);

                    return ['index' => [101 => [10 => 100]]];
                }

                $this->assertSame($secondParentMock, $parentProduct);

                return ['index' => [101 => [11 => 200]]];
            });

        $attributesCall = 0;
        $this->configurableAttributeDataMock->expects($this->exactly(2))
            ->method('getAttributesData')
            ->willReturnCallback(function (Product $parentProduct, array $options) use (
                &$attributesCall,
                $firstParentMock,
                $secondParentMock
            ) {
                if ($attributesCall++ === 0) {
                    $this->assertSame($firstParentMock, $parentProduct);
                    $this->assertSame(['index' => [101 => [10 => 100]]], $options);

                    return [
                        'attributes' => [
                            10 => [
                                'code' => 'athos_color',
                                'options' => [
                                    ['id' => 100, 'label' => 'Red'],
                                ],
                            ],
                        ],
                    ];
                }

                $this->assertSame($secondParentMock, $parentProduct);
                $this->assertSame(['index' => [101 => [11 => 200]]], $options);

                return [
                    'attributes' => [
                        11 => [
                            'code' => 'athos_size',
                            'options' => [
                                ['id' => 200, 'label' => 'Large'],
                            ],
                        ],
                    ],
                ];
            });

        $result = $this->provider->getData($rows, $feedSpecificationMock);

        $this->assertSame(
            json_encode(['athos_color' => ['value' => 'Red']]),
            $result[0][SelectedOptionsProvider::FIELD_KEY_SELECTED_OPTIONS]
        );
        $this->assertSame(
            json_encode(['athos_size' => ['value' => 'Large']]),
            $result[1][SelectedOptionsProvider::FIELD_KEY_SELECTED_OPTIONS]
        );
    }
}
