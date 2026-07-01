<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\ViewModel;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Service\Config;
use AthosCommerce\Feed\Service\Tracking\OrderDataResolverInterface;
use AthosCommerce\Feed\ViewModel\CheckoutSuccessViewModel;
use Magento\Checkout\Model\Session;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(CheckoutSuccessViewModel::class)]
final class CheckoutSuccessViewModelTest extends TestCase
{
    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var Session|MockObject
     */
    private $checkoutSessionMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var AthosCommerceLogger
     */
    private $loggerStub;

    /**
     * @var OrderDataResolverInterface|MockObject
     */
    private $orderDataResolverMock;

    /**
     * @var CheckoutSuccessViewModel
     */
    private $viewModel;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->checkoutSessionMock = $this->createMock(Session::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->loggerStub = $this->createStub(AthosCommerceLogger::class);
        $this->orderDataResolverMock = $this->createMock(OrderDataResolverInterface::class);

        $this->viewModel = new CheckoutSuccessViewModel(
            $this->configMock,
            $this->checkoutSessionMock,
            $this->serializerMock,
            $this->loggerStub,
            $this->orderDataResolverMock
        );
    }

    public function testGetSuccessPageConfigReturnsSerializedEmptyArrayWhenRenderingIsDisabled(): void
    {
        $this->configMock->expects($this->once())
            ->method('shouldRender')
            ->willReturn(false);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with([])
            ->willReturn('[]');

        $this->checkoutSessionMock->expects($this->never())
            ->method('getLastRealOrder');

        $this->orderDataResolverMock->expects($this->never())
            ->method('resolve');

        $this->assertSame('[]', $this->viewModel->getSuccessPageConfig());
    }

    public function testGetSuccessPageConfigReturnsSerializedEmptyArrayWhenOrderIsMissing(): void
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

        $this->orderDataResolverMock->expects($this->never())
            ->method('resolve');

        $this->assertSame('[]', $this->viewModel->getSuccessPageConfig());
    }

    public function testGetSuccessPageConfigReturnsSerializedEmptyArrayWhenOrderHasNoId(): void
    {
        $orderMock = $this->createMock(Order::class);

        $this->configMock->expects($this->once())
            ->method('shouldRender')
            ->willReturn(true);

        $this->checkoutSessionMock->expects($this->once())
            ->method('getLastRealOrder')
            ->willReturn($orderMock);

        $orderMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with([])
            ->willReturn('[]');

        $this->orderDataResolverMock->expects($this->never())
            ->method('resolve');

        $this->assertSame('[]', $this->viewModel->getSuccessPageConfig());
    }

    public function testGetSuccessPageConfigReturnsSerializedResolvedOrderData(): void
    {
        $orderMock = $this->createMock(Order::class);

        $resolvedPayload = [
            'orderId' => 'ORD#100001234',
            'products' => [
                ['uid' => '555', 'sku' => 'SUCCESS-SKU'],
            ],
        ];

        $this->configMock->expects($this->once())
            ->method('shouldRender')
            ->willReturn(true);

        $this->checkoutSessionMock->expects($this->once())
            ->method('getLastRealOrder')
            ->willReturn($orderMock);

        $orderMock->expects($this->once())
            ->method('getId')
            ->willReturn(100001234);

        $this->orderDataResolverMock->expects($this->once())
            ->method('resolve')
            ->with($orderMock)
            ->willReturn($resolvedPayload);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($resolvedPayload)
            ->willReturn('serialized-json');

        $this->assertSame('serialized-json', $this->viewModel->getSuccessPageConfig());
    }

    public function testGetSuccessPageConfigLogsErrorAndReturnsSerializedEmptyArrayWhenResolverThrows(): void
    {
        $orderMock = $this->createMock(Order::class);
        $loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->viewModel = new CheckoutSuccessViewModel(
            $this->configMock,
            $this->checkoutSessionMock,
            $this->serializerMock,
            $loggerMock,
            $this->orderDataResolverMock
        );

        $exception = new \RuntimeException('resolver failed');

        $this->configMock->expects($this->once())
            ->method('shouldRender')
            ->willReturn(true);

        $this->checkoutSessionMock->expects($this->once())
            ->method('getLastRealOrder')
            ->willReturn($orderMock);

        $orderMock->expects($this->once())
            ->method('getId')
            ->willReturn(100001234);

        $this->orderDataResolverMock->expects($this->once())
            ->method('resolve')
            ->with($orderMock)
            ->willThrowException($exception);

        $loggerMock->expects($this->once())
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

        $this->assertSame('[]', $this->viewModel->getSuccessPageConfig());
    }
}
