<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\ViewModel;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Service\Config;
use AthosCommerce\Feed\Service\Tracking\CompositeQuoteItemPriceResolver;
use AthosCommerce\Feed\Service\Tracking\CompositeSkuResolver;
use AthosCommerce\Feed\Service\Tracking\SkuResolverInterface;
use AthosCommerce\Feed\ViewModel\CartViewModel;
use Magento\Checkout\Model\Session;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\TestCase;

class CartViewModelTest extends TestCase
{
    private $configMock;
    private $priceResolverMock;
    private $skuResolverMock;
    private $serializerMock;
    private $checkoutSessionMock;
    private $loggerMock;
    private $viewModel;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->priceResolverMock = $this->createMock(CompositeQuoteItemPriceResolver::class);
        $this->skuResolverMock = $this->createMock(CompositeSkuResolver::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->checkoutSessionMock = $this->createMock(Session::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->viewModel = new CartViewModel(
            $this->configMock,
            $this->priceResolverMock,
            $this->skuResolverMock,
            $this->serializerMock,
            $this->checkoutSessionMock,
            $this->loggerMock
        );
    }


    public function testReturnsEmptyArrayWhenRenderingDisabled(): void
    {
        $this->configMock->expects($this->once())->method('shouldRender')->willReturn(false);

        $this->assertSame('', $this->viewModel->getCartPageData());
    }

    public function testReturnsEmptyProductsWhenQuoteDoesNotExist(): void
    {
        $quote = $this->createMock(Quote::class);

        $this->configMock->method('shouldRender')->willReturn(true);

        $quote->method('getId')->willReturn(null);

        $this->checkoutSessionMock->method('getQuote')->willReturn($quote);

        $this->assertSame(
            '',
            $this->viewModel->getCartPageData()
        );
    }

    public function testReturnsEmptyProductsWhenNoVisibleItemsExist(): void
    {
        $quote = $this->createMock(Quote::class);

        $this->configMock->method('shouldRender')->willReturn(true);

        $quote->method('getId')->willReturn(1);
        $quote->method('getAllVisibleItems')->willReturn([]);

        $this->checkoutSessionMock->method('getQuote')->willReturn($quote);

        $this->assertSame(
            '',
            $this->viewModel->getCartPageData()
        );
    }

    public function testReturnsSerializedProducts(): void
    {
        $quote = $this->createMock(Quote::class);
        $item = $this->createMock(Item::class);

        $this->configMock->method('shouldRender')->willReturn(true);

        $quote->method('getId')->willReturn(1);
        $quote->method('getAllVisibleItems')->willReturn([$item]);

        $this->checkoutSessionMock->method('getQuote')->willReturn($quote);

        $item->method('getItemId')->willReturn(1890);
        $item->method('getName')->willReturn('Test Athos Product');
        $item->method('getQty')->willReturn(2);

        $this->skuResolverMock
            ->expects($this->once())
            ->method('getProductSku')
            ->with($item)
            ->willReturn('SKU-90001');

        $this->priceResolverMock
            ->expects($this->once())
            ->method('getProductPrice')
            ->with($item)
            ->willReturn(92349.99);

        $expectedProducts = [[
            'uid' => '1890',
            'name' => 'Test Athos Product',
            'sku' => 'SKU-90001',
            'qty' => 2,
            'price' => 92349.99,
        ]];

        $this->serializerMock
            ->expects($this->once())
            ->method('serialize')
            ->with($expectedProducts)
            ->willReturn('serialized-products');

        $this->assertSame(
            'serialized-products',
            $this->viewModel->getCartPageData()
        );
    }

    public function testLogsErrorAndReturnsEmptyProductsWhenExceptionOccurs(): void
    {
        $this->configMock->method('shouldRender')->willReturn(true);

        $this->checkoutSessionMock
            ->method('getQuote')
            ->willThrowException(new \RuntimeException('Something failed'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Something failed');

        $this->assertSame('', $this->viewModel->getCartPageData());
    }

    public function testUsesResolverFromPool(): void
    {
        $product = $this->createConfiguredMock(
            CartItemInterface::class,
            ['getProductType' => 'configurable']
        );

        $resolver = $this->createMock(SkuResolverInterface::class);
        $resolver->method('getProductSku')->willReturn('configurable-sku');

        $defaultResolver = $this->createMock(SkuResolverInterface::class);

        $subject = new CompositeSkuResolver(
            $this->loggerMock,
            $defaultResolver,
            ['configurable' => $resolver]
        );

        $this->assertSame('configurable-sku', $subject->getProductSku($product));
    }

    public function testFallsBackToDefaultResolver(): void
    {
        $product = $this->createConfiguredMock(CartItemInterface::class, ['getProductType' => 'simple']);

        $defaultResolver = $this->createMock(SkuResolverInterface::class);

        $defaultResolver->method('getProductSku')->willReturn('DEFAULT');

        $subject = new CompositeSkuResolver(
            $this->loggerMock,
            $defaultResolver,
            []
        );

        $this->assertSame('DEFAULT', $subject->getProductSku($product));
    }
}
