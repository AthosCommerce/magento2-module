<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\MinMaxPricesProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\DataProvider\Price\BasePriceProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\PricesProvider;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MinMaxPricesProviderTest extends TestCase
{
    private ParentVariantResolver&MockObject $parentVariantResolverMock;

    private ConfigurableOptionsProviderInterface&MockObject $configurableOptionsProviderMock;

    private BasePriceProvider&MockObject $basePriceProviderMock;

    private MinMaxPricesProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parentVariantResolverMock = $this->createMock(ParentVariantResolver::class);
        $this->configurableOptionsProviderMock = $this->createMock(ConfigurableOptionsProviderInterface::class);
        $this->basePriceProviderMock = $this->createMock(BasePriceProvider::class);

        $this->provider = new MinMaxPricesProvider(
            $this->parentVariantResolverMock,
            $this->configurableOptionsProviderMock,
            $this->basePriceProviderMock,
            $this->createMock(AthosCommerceLogger::class)
        );
    }

    public function testGetDataSkipsStandaloneRows(): void
    {
        $productModelMock = $this->createMock(Product::class);
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);

        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);

        $row = [
            'entity_id' => 10,
            'product_model' => $productModelMock,
            Constant::IS_STANDALONE_PRODUCT_KEY => true,
        ];

        $this->parentVariantResolverMock->expects($this->never())
            ->method('resolveParentProductForRow');

        $this->assertSame([$row], $this->provider->getData([$row], $feedSpecificationMock));
    }

    public function testGetDataAggregatesParentAwareRowsAndReusesCache(): void
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);

        $rowProductA = $this->createMock(Product::class);
        $rowProductB = $this->createMock(Product::class);

        $parentProductMock = $this->createMock(Product::class);
        $parentProductMock->method('getId')->willReturn(55);
        $parentProductMock->method('getTypeId')->willReturn(Configurable::TYPE_CODE);

        $variantA = $this->createMock(ProductInterface::class);
        $variantB = $this->createMock(ProductInterface::class);

        $rows = [
            [
                'entity_id' => 101,
                'product_model' => $rowProductA,
                Constant::IS_STANDALONE_PRODUCT_KEY => false,
            ],
            [
                'entity_id' => 102,
                'product_model' => $rowProductB,
                Constant::IS_STANDALONE_PRODUCT_KEY => false,
            ],
        ];

        $this->parentVariantResolverMock->expects($this->exactly(2))
            ->method('resolveParentProductForRow')
            ->willReturn($parentProductMock);

        $this->configurableOptionsProviderMock->expects($this->once())
            ->method('getProducts')
            ->with($parentProductMock)
            ->willReturn([$variantA, $variantB]);

        $this->basePriceProviderMock->expects($this->exactly(2))
            ->method('getPrices')
            ->willReturnOnConsecutiveCalls(
                [
                    PricesProvider::REGULAR_PRICE_KEY => 10.0,
                    PricesProvider::FINAL_PRICE_KEY => 8.0,
                    PricesProvider::MAX_PRICE_KEY => 11.0,
                ],
                [
                    PricesProvider::REGULAR_PRICE_KEY => 25.0,
                    PricesProvider::FINAL_PRICE_KEY => 20.0,
                    PricesProvider::MAX_PRICE_KEY => 27.0,
                ]
            );

        $result = $this->provider->getData($rows, $feedSpecificationMock);

        $expectedPrices = [
            'ss_minimums' => [
                PricesProvider::REGULAR_PRICE_KEY => 10.0,
                PricesProvider::FINAL_PRICE_KEY => 8.0,
                PricesProvider::MAX_PRICE_KEY => 11.0,
            ],
            'ss_maximums' => [
                PricesProvider::REGULAR_PRICE_KEY => 25.0,
                PricesProvider::FINAL_PRICE_KEY => 20.0,
                PricesProvider::MAX_PRICE_KEY => 27.0,
            ],
        ];

        $this->assertSame(array_merge($rows[0], $expectedPrices), $result[0]);
        $this->assertSame(array_merge($rows[1], $expectedPrices), $result[1]);
    }
}
