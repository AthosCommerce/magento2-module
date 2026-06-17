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
class CheckoutViewModelTest extends TestCase
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
     * @var Order|MockObject
     */
    private $orderMock;

    /**
     * @var CheckoutViewModel
     */
    private $viewModel;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->priceResolverMock = $this->createMock(CompositeOrderItemPriceResolver::class);
        $this->checkoutSessionMock = $this->createMock(Session::class);
        $this->skuResolverMock = $this->createMock(CompositeSkuResolver::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->orderMock = $this->createMock(Order::class);

        $this->checkoutSessionMock->method('getLastRealOrder')
            ->willReturn($this->orderMock);

        $this->orderMock->method('getId')
            ->willReturn(12345);

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

        $this->assertSame('site_1565', $this->viewModel->getAthoscommerceSiteId());
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

    public function testGetOrderIdReturnsOrderId(): void
    {
        $this->orderMock->method('getId')
            ->willReturn(12345);

        $this->assertSame(12345, $this->viewModel->getOrderId());
    }

    public function testGetOrderIdReturnsNullWhenOrderIsMissing(): void
    {
        $checkoutSessionMock = $this->createMock(Session::class);

        $checkoutSessionMock->method('getLastRealOrder')
            ->willReturn(null);

        $viewModel = new CheckoutViewModel(
            $this->configMock,
            $this->priceResolverMock,
            $checkoutSessionMock,
            $this->skuResolverMock,
            $this->serializerMock
        );

        $this->assertNull($viewModel->getOrderId());
    }

    public function testGetSuccessPageConfigReturnsSerializedConfig(): void
    {
        $billingAddressMock = $this->createMock(OrderAddressInterface::class);
        $orderItem = $this->createMock(OrderItem::class);

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
            'orderId' => '12345',
            'totals' => [
                'transactionTotal' => 500.50,
                'total' => 450.00,
                'city' => 'London',
                'state' => 'London Region',
                'country' => 'GB',
            ],
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

        $result = $this->viewModel->getSuccessPageConfig();

        $this->assertSame(json_encode($expectedConfig), $result);
    }

    public function testGetSuccessPageConfigReturnsSerializedEmptyArrayWhenOrderIsMissing(): void
    {
        $checkoutSessionMock = $this->createMock(Session::class);

        $checkoutSessionMock->method('getLastRealOrder')
            ->willReturn(null);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with([])
            ->willReturn('[]');

        $viewModel = new CheckoutViewModel(
            $this->configMock,
            $this->priceResolverMock,
            $checkoutSessionMock,
            $this->skuResolverMock,
            $this->serializerMock
        );

        $this->assertSame('[]', $viewModel->getSuccessPageConfig());
    }
}
