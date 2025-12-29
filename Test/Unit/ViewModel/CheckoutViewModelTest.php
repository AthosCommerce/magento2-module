<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\ViewModel;

use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\ViewModel\CheckoutViewModel;
use AthosCommerce\Feed\Service\Config;
use AthosCommerce\Feed\Service\Tracking\CompositeOrderItemPriceResolver;
use AthosCommerce\Feed\Service\Tracking\CompositeSkuResolver;
use Magento\Checkout\Model\Session;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * @covers \AthosCommerce\Feed\ViewModel\CheckoutViewModel
 */
class CheckoutViewModelTest extends TestCase
{
    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configMock;
    /**
     * @var CompositeOrderItemPriceResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    private $priceResolverMock;
    /**
     * @var Session|\PHPUnit\Framework\MockObject\MockObject
     */
    private $checkoutSessionMock;
    /**
     * @var CompositeSkuResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    private $skuResolverMock;
    /**
     * @var SerializerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $serializerMock;
    /**
     * @var OrderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderMock;

    /**
     * @var CheckoutViewModel
     */
    private $viewModel;

    /**
     * Setup test dependencies
     */
    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->priceResolverMock = $this->createMock(CompositeOrderItemPriceResolver::class);
        $this->checkoutSessionMock = $this->createMock(Session::class);
        $this->skuResolverMock = $this->createMock(CompositeSkuResolver::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->orderMock = $this->createMock(OrderInterface::class);

        $this->checkoutSessionMock->method('getLastRealOrder')
            ->willReturn($this->orderMock);

        $this->viewModel = new CheckoutViewModel(
            $this->configMock,
            $this->priceResolverMock,
            $this->checkoutSessionMock,
            $this->skuResolverMock,
            $this->serializerMock
        );
    }

    public function testGetAthoscommerceSiteId(): void
    {
        $this->configMock->expects($this->once())
            ->method('getSiteId')
            ->willReturn('site_1565');

        $this->assertSame(
            'site_1565',
            $this->viewModel->getAthoscommerceSiteId()
        );
    }

    public function testGetProductsReturnsSerializedProducts(): void
    {
        $orderItem = $this->createMock(OrderItemInterface::class);

        $orderItem->expects($this->once())
            ->method('getParentItem')
            ->willReturn(null);

        $orderItem->expects($this->once())
            ->method('getQtyOrdered')
            ->willReturn(3);

        $this->priceResolverMock->expects($this->once())
            ->method('getProductPrice')
            ->with($orderItem)
            ->willReturn(150.00);

        $this->skuResolverMock->expects($this->once())
            ->method('getProductSku')
            ->with($orderItem)
            ->willReturn('ORDER-SKU-1');

        $this->orderMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$orderItem]);

        $expectedProducts = [
            [
                'price' => 150.00,
                'sku' => 'ORDER-SKU-1',
                'qty' => 3
            ]
        ];

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($expectedProducts)
            ->willReturn(json_encode($expectedProducts));

        $result = $this->viewModel->getProducts();

        $this->assertSame(
            json_encode($expectedProducts),
            $result
        );
    }

    public function testGetProductsSkipsChildItems(): void
    {
        $parentItem = $this->createMock(OrderItemInterface::class);
        $childItem = $this->createMock(OrderItemInterface::class);

        $childItem->expects($this->once())
            ->method('getParentItem')
            ->willReturn($parentItem);

        $parentItem->expects($this->once())
            ->method('getParentItem')
            ->willReturn(null);

        $parentItem->method('getQtyOrdered')->willReturn(1);

        $this->priceResolverMock->method('getProductPrice')->willReturn(50.49);
        $this->skuResolverMock->method('getProductSku')->willReturn('PARENT-SKU');

        $this->orderMock->method('getAllVisibleItems')
            ->willReturn([$childItem, $parentItem]);

        $this->serializerMock->method('serialize')
            ->willReturn(json_encode([
                [
                    'price' => 50.49,
                    'sku' => 'PARENT-SKU',
                    'qty' => 1
                ]
            ]));

        $result = $this->viewModel->getProducts();

        $this->assertNotNull($result);
    }

    public function testGetOrderIdReturnsOrderId(): void
    {
        $this->orderMock->expects($this->once())
            ->method('getDataUsingMethod')
            ->with('id')
            ->willReturn(12345);

        $this->assertSame(
            12345,
            $this->viewModel->getOrderId()
        );
    }

    public function testGetOrderIdReturnsOrderIdWithPrefix(): void
    {
        $this->orderMock->expects($this->once())
            ->method('getDataUsingMethod')
            ->with('id')
            ->willReturn('ORD-789456123');

        $this->assertSame(
            'ORD-789456123',
            $this->viewModel->getOrderId()
        );
    }

    public function testGetOrderIdReturnsNullWhenOrderIsMissing(): void
    {
        $this->checkoutSessionMock->method('getLastRealOrder')
            ->willReturn(null);

        $viewModel = new CheckoutViewModel(
            $this->configMock,
            $this->priceResolverMock,
            $this->checkoutSessionMock,
            $this->skuResolverMock,
            $this->serializerMock
        );

        $this->assertNull($viewModel->getOrderId());
    }
}
