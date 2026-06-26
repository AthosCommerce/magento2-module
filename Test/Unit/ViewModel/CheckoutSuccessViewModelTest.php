<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\ViewModel;


use AthosCommerce\Feed\Service\Config;
use AthosCommerce\Feed\Service\Tracking\CompositeOrderItemPriceResolver;
use AthosCommerce\Feed\Service\Tracking\CompositeSkuResolver;
use AthosCommerce\Feed\ViewModel\CheckoutSuccessViewModel;
use Magento\Checkout\Model\Session;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AthosCommerce\Feed\ViewModel\CheckoutSuccessViewModel
 */
class CheckoutSuccessViewModelTest extends TestCase
{
    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var CompositeOrderItemPriceResolver|MockObject
     */
    private $priceResolverMock;

    /**
     * @var Session|MockObject
     */
    private $checkoutSessionMock;

    /**
     * @var CompositeSkuResolver|MockObject
     */
    private $skuResolverMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var AthosCommerceLogger|MockObject
     */
    private $loggerMock;

    /**
     * @var OrderDataResolverInterface|MockObject
     */
    private $orderDataResolverMock;

    /**
     * @var Order|MockObject
     */
    private $orderMock;

    /**
     * @var CheckoutSuccessViewModel
     */
    private $viewModel;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->checkoutSessionMock = $this->createMock(Session::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);
        $this->orderDataResolverMock = $this->createMock(OrderDataResolverInterface::class);
        $this->orderMock = $this->createMock(Order::class);

        $this->orderMock->method('getId')->willReturn(100001234);
        $this->checkoutSessionMock->method('getLastRealOrder')->willReturn($this->orderMock);

        $this->viewModel = new CheckoutSuccessViewModel(
            $this->configMock,
            $this->checkoutSessionMock,
            $this->serializerMock,
            $this->loggerMock,
            $this->orderDataResolverMock
        );
    }

    public function testReturnsSerializedEmptyArrayWhenRenderingIsDisabled(): void
    {
        $this->priceResolverMock = $this->createMock(CompositeOrderItemPriceResolver::class);
        $this->checkoutSessionMock = $this->createMock(Session::class);
        $this->skuResolverMock = $this->createMock(CompositeSkuResolver::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->orderMock = $this->createMock(Order::class);

        $this->checkoutSessionMock->method('getLastRealOrder')->willReturn($this->orderMock);

        $this->orderMock->method('getId')->willReturn('ORD#100001234');

        $this->viewModel = new CheckoutSuccessViewModel(
            $this->configMock,
            $this->priceResolverMock,
            $this->checkoutSessionMock,
            $this->skuResolverMock,
            $this->serializerMock
        );
    }

    public function testGetSuccessPageConfigReturnsEmptyArrayWhenRenderingDisabled(): void

    {
        $this->configMock->expects($this->once())
            ->method('shouldRender')
            ->willReturn(false);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with([])
            ->willReturn('[]');

        $this->orderDataResolverMock->expects($this->never())
            ->method('resolve');

        $this->assertSame('[]', $this->viewModel->getSuccessPageConfig());
    }

    public function testReturnsSerializedEmptyArrayWhenOrderIsMissing(): void
    {
        $this->configMock->expects($this->once())
            ->method('shouldRender')
            ->willReturn(true);

        $this->checkoutSessionMock->expects($this->once())
            ->method('getLastRealOrder')
            ->willReturn(null);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with([])
            ->willReturn('[]');

        $this->orderDataResolverMock->expects($this->once())
            ->method('resolve');

        $this->assertSame('[]', $this->viewModel->getSuccessPageConfig());
    }

    public function testReturnsSerializedEmptyArrayWhenOrderHasNoId(): void
    {
        $this->configMock->expects($this->once())
            ->method('shouldRender')
            ->willReturn(true);

        $this->orderMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with([])
            ->willReturn('[]');

        $this->orderDataResolverMock->expects($this->once())
            ->method('resolve');

        $this->assertSame('[]', $this->viewModel->getSuccessPageConfig());
    }

    public function testReturnsSerializedResolvedOrderData(): void
    {
        $expectedPayload = [
            'orderId' => 'ORD#100001234',
            'products' => [
                ['uid' => '555', 'sku' => 'SUCCESS-SKU'],
            ],
        ];

        $this->configMock->expects($this->once())
            ->method('shouldRender')
            ->willReturn(true);

        $this->orderMock->expects($this->once())
            ->method('getId')
            ->willReturn(100001234);

        $this->orderDataResolverMock->expects($this->once())
            ->method('resolve')
            ->with($this->orderMock)
            ->willReturn($expectedPayload);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($expectedPayload)
            ->willReturn('serialized-json');

        $this->assertSame('serialized-json', $this->viewModel->getSuccessPageConfig());
    }

    public function testReturnsSerializedEmptyArrayWhenResolverThrows(): void
    {
        $exception = new RuntimeException('resolver failed');
        $this->assertSame('[]', $this->viewModel->getSuccessPageConfig());
    }

    public function testGetProductsReturnsProductsArray(): void
    {
        $orderItem = $this->createMock(OrderItem::class);

        $orderItem->expects($this->once())
            ->method('getParentItem')
            ->willReturn(null);

        $orderItem->expects($this->any())
            ->method('getProductId')
            ->willReturn(999);

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

        $result = $this->viewModel->getProducts();

        $this->assertSame(
            [
                [
                    'uid' => '999',
                    'sku' => 'ORDER-SKU-1',
                    'parentId' => null,
                    'qty' => 3,
                    'price' => 150.00,
                ],
            ],
            $result
        );
    }

    public function testGetProductsIncludesParentIdWhenParentExists(): void
    {
        $parentItem = $this->createMock(OrderItem::class);
        $childItem = $this->createMock(OrderItem::class);

        $childItem->expects($this->once())
            ->method('getParentItem')
            ->willReturn($parentItem);

        $childItem->expects($this->any())
            ->method('getProductId')
            ->willReturn(111);

        $childItem->expects($this->once())
            ->method('getQtyOrdered')
            ->willReturn(2);

        $parentItem->expects($this->any())
            ->method('getProductId')
            ->willReturn(222);

        $this->priceResolverMock->expects($this->once())
            ->method('getProductPrice')
            ->with($childItem)
            ->willReturn(50.49);

        $this->skuResolverMock->expects($this->once())
            ->method('getProductSku')
            ->with($childItem)
            ->willReturn('CHILD-SKU');

        $this->orderMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$childItem]);

        $result = $this->viewModel->getProducts();

        $this->assertSame(
            [
                [
                    'uid' => '111',
                    'sku' => 'CHILD-SKU',
                    'parentId' => '222',
                    'qty' => 2,
                    'price' => 50.49,
                ],
            ],
            $result
        );
    }

    public function testGetSuccessPageConfigReturnsSerializedConfig(): void
    {
        $billingAddressMock = $this->createMock(OrderAddressInterface::class);
        $orderItem = $this->createMock(OrderItem::class);

        $viewModel = new CheckoutSuccessViewModel(
            $this->configMock,
            $this->priceResolverMock,
            $this->checkoutSessionMock,
            $this->skuResolverMock,
            $this->serializerMock
        );

        $orderItem->method('getParentItem')->willReturn(null);
        $orderItem->method('getProductId')->willReturn(555);
        $orderItem->method('getQtyOrdered')->willReturn(4);

        $this->priceResolverMock->expects($this->once())
            ->method('getProductPrice')
            ->with($orderItem)
            ->willReturn(299.99);

        $this->skuResolverMock->expects($this->once())
            ->method('getProductSku')
            ->with($orderItem)
            ->willReturn('SUCCESS-SKU');

        $billingAddressMock->expects($this->once())
            ->method('getCity')
            ->willReturn('London');

        $billingAddressMock->expects($this->once())
            ->method('getRegion')
            ->willReturn('London Region');

        $billingAddressMock->expects($this->once())
            ->method('getCountryId')
            ->willReturn('GB');

        $this->orderMock->method('getAllVisibleItems')
            ->willReturn([$orderItem]);

        $this->orderMock->expects($this->once())
            ->method('getGrandTotal')
            ->willReturn(500.50);

        $this->orderMock->expects($this->once())
            ->method('getSubtotal')
            ->willReturn(450.00);

        $this->orderMock->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($billingAddressMock);

        $expectedConfig = [
            'orderId' => 'ORD#100001234',
            'subTotal' => 500.50,
            'total' => 450.00,
            'city' => 'London',
            'state' => 'London Region',
            'country' => 'GB',
            'products' => [
                [
                    'uid' => '555',
                    'sku' => 'SUCCESS-SKU',
                    'parentId' => null,
                    'qty' => 4,
                    'price' => 299.99,
                ],
            ],
        ];

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($expectedConfig)
            ->willReturn(json_encode($expectedConfig));

        $this->configMock->expects($this->once())
            ->method('shouldRender')
            ->willReturn(true);


        $this->orderMock->expects($this->once())
            ->method('getId')
            ->willReturn(100001234);

        $this->orderDataResolverMock->expects($this->once())
            ->method('resolve')
            ->with($this->orderMock)
            ->willThrowException($exception);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                'Failed to resolve Athos Commerce checkout success payload',
                $this->callback(static function (array $context) use ($exception): bool {
                    return isset($context['exception']) && $context['exception'] === $exception;
                })
            );

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with([])
            ->willReturn('[]');


        $viewModel = new CheckoutSuccessViewModel(
            $configMock,
            $this->priceResolverMock,
            $checkoutSessionMock,
            $this->skuResolverMock,
            $this->serializerMock
        );

        $this->assertSame('[]', $viewModel->getSuccessPageConfig());

    }
}
