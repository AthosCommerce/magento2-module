<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\ViewModel;

use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\ViewModel\CartViewModel;
use AthosCommerce\Feed\Service\Config;
use AthosCommerce\Feed\Service\Tracking\CompositeQuoteItemPriceResolver;
use AthosCommerce\Feed\Service\Tracking\CompositeSkuResolver;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\Data\CartItemInterface;

class CartViewModelTest extends TestCase
{
    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configMock;

    /**
     * @var CompositeQuoteItemPriceResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    private $priceResolverMock;

    /**
     * @var CompositeSkuResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    private $skuResolverMock;

    /**
     * @var SerializerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $serializerMock;

    /**
     * @var CartViewModel
     */
    private $viewModel;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->priceResolverMock = $this->createMock(CompositeQuoteItemPriceResolver::class);
        $this->skuResolverMock = $this->createMock(CompositeSkuResolver::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);

        $this->viewModel = new CartViewModel(
            $this->configMock,
            $this->priceResolverMock,
            $this->skuResolverMock,
            $this->serializerMock
        );
    }

    public function testGetAthoscommerceSiteId(): void
    {
        $this->configMock
            ->expects($this->once())
            ->method('getSiteId')
            ->willReturn('site_456');

        $this->assertSame(
            'site_456',
            $this->viewModel->getAthoscommerceSiteId()
        );
    }

    public function testGetProductsReturnsSerializedProducts(): void
    {
        $quoteItem = $this->createMock(CartItemInterface::class);

        $quoteItem->expects($this->once())
            ->method('getQty')
            ->willReturn(5);

        $this->skuResolverMock->expects($this->exactly(2))
            ->method('getProductSku')
            ->with($quoteItem)
            ->willReturn('SKU-156');

        $this->priceResolverMock->expects($this->once())
            ->method('getProductPrice')
            ->with($quoteItem)
            ->willReturn(199.99);

        $expectedProductsArray = [
            [
                'price' => 199.99,
                'sku' => 'SKU-156',
                'qty' => 5
            ]
        ];

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($expectedProductsArray)
            ->willReturn(json_encode($expectedProductsArray));

        $result = $this->viewModel->getProducts([$quoteItem]);

        $this->assertSame(
            json_encode($expectedProductsArray),
            $result
        );
    }

    public function testGetProductsSkuReturnsSerializedSkus(): void
    {
        $quoteItem = $this->createMock(CartItemInterface::class);

        $quoteItem->method('getQty')->willReturn(1);

        $this->skuResolverMock->method('getProductSku')
            ->willReturn('Athos-sku-741');

        $this->priceResolverMock->method('getProductPrice')
            ->willReturn(10.45);

        $this->serializerMock->method('serialize')
            ->willReturnCallback(static function ($data) {
                return json_encode($data);
            });

        $this->viewModel->getProducts([$quoteItem]);

        $result = $this->viewModel->getProductsSku();

        $this->assertSame(
            json_encode(['Athos-sku-741']),
            $result
        );
    }
}
