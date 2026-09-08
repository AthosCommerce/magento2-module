<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Service\Tracking;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Service\Tracking\CartItemIdentityResolver;
use AthosCommerce\Feed\Service\Tracking\CartMetaProvider;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CartMetaProviderTest extends TestCase
{
    /**
     * @var CheckoutSession|MockObject
     */
    private $checkoutSessionMock;

    /**
     * @var AthosCommerceLogger|MockObject
     */
    private $loggerMock;

    /**
     * @var CartItemIdentityResolver|MockObject
     */
    private $trackingMetaResolverMock;

    /**
     * @var Http|MockObject
     */
    private $requestMock;

    protected function setUp(): void
    {
        $this->checkoutSessionMock = $this->createMock(CheckoutSession::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);
        $this->trackingMetaResolverMock = $this->createMock(CartItemIdentityResolver::class);
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array('getModuleName', 'getControllerName', 'getActionName'))
            ->getMock();
    }

    public function testGetReturnsEmptyArrayWhenRouteIsNotAllowed(): void
    {
        $provider = $this->createProvider(array('checkout_cart_index'));

        $this->requestMock->method('getModuleName')->willReturn('catalog');
        $this->requestMock->method('getControllerName')->willReturn('product');
        $this->requestMock->method('getActionName')->willReturn('view');

        $this->checkoutSessionMock->expects($this->never())
            ->method('getQuote');

        $this->assertSame(array(), $provider->get());
    }

    public function testGetReturnsEmptyArrayWhenQuoteIsNull(): void
    {
        $provider = $this->createProvider();

        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn(null);

        $this->assertSame(array(), $provider->get());
    }

    public function testGetReturnsProductsForVisibleItems(): void
    {
        $provider = $this->createProvider();

        $cartItemOne = $this->createCartItemMock();
        $cartItemTwo = $this->createCartItemMock();
        $quote = $this->createQuoteMock();

        $quote->method('getAllVisibleItems')->willReturn(array($cartItemOne, $cartItemTwo));

        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);

        $cartItemOne->method('getDataUsingMethod')->willReturnCallback(function ($field) {
            return match ($field) {
                'qty' => 1.0,
                'price' => 0.0,
                default => null,
            };
        });
        $cartItemOne->method('getProductType')->willReturn('simple');

        $cartItemTwo->method('getDataUsingMethod')->willReturnCallback(function ($field) {
            return match ($field) {
                'qty' => 2.0,
                'price' => 0.0,
                default => null,
            };
        });
        $cartItemTwo->method('getProductType')->willReturn('configurable');

        $uidCalls = array();
        $this->trackingMetaResolverMock->expects($this->exactly(2))
            ->method('getUid')
            ->willReturnCallback(function ($cartItem) use ($cartItemOne, $cartItemTwo, &$uidCalls) {
                $uidCalls[] = $cartItem;
                if ($cartItem === $cartItemOne) {
                    return '17';
                }
                if ($cartItem === $cartItemTwo) {
                    return '9';
                }

                return '';
            });

        $parentIdCalls = array();
        $this->trackingMetaResolverMock->expects($this->exactly(2))
            ->method('getParentId')
            ->willReturnCallback(function ($cartItem) use ($cartItemOne, $cartItemTwo, &$parentIdCalls) {
                $parentIdCalls[] = $cartItem;
                if ($cartItem === $cartItemOne) {
                    return '17';
                }
                if ($cartItem === $cartItemTwo) {
                    return '12';
                }

                return '';
            });

        $skuCalls = array();
        $this->trackingMetaResolverMock->expects($this->exactly(2))
            ->method('getSku')
            ->willReturnCallback(function ($cartItem) use ($cartItemOne, $cartItemTwo, &$skuCalls) {
                $skuCalls[] = $cartItem;
                if ($cartItem === $cartItemOne) {
                    return 'simple-sku';
                }
                if ($cartItem === $cartItemTwo) {
                    return 'child-sku';
                }

                return '';
            });

        $result = $provider->get();

        $this->assertSame(array($cartItemOne, $cartItemTwo), $uidCalls);
        $this->assertSame(array($cartItemOne, $cartItemTwo), $parentIdCalls);
        $this->assertSame(array($cartItemOne, $cartItemTwo), $skuCalls);

        $this->assertSame(
            array(
                'products' => array(
                    array(
                        'key' => '17::::17',
                        'uid' => '17',
                        'parentId' => '17',
                        'sku' => 'simple-sku',
                        'qty' => 1.0,
                        'price' => 0.0,
                        'productType' => 'simple',
                    ),
                    array(
                        'key' => '12::::12_9',
                        'uid' => '12_9',
                        'parentId' => '12',
                        'sku' => 'child-sku',
                        'qty' => 2.0,
                        'price' => 0.0,
                        'productType' => 'configurable',
                    ),
                ),
            ),
            $result
        );
    }

    public function testGetFallsBackToUidWhenParentIdIsEmpty(): void
    {
        $provider = $this->createProvider();

        $cartItem = $this->createCartItemMock();
        $quote = $this->createQuoteMock();

        $quote->method('getAllVisibleItems')->willReturn(array($cartItem));

        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);

        $cartItem->method('getDataUsingMethod')->willReturnCallback(function ($field) {
            return match ($field) {
                'qty' => 3.0,
                'price' => 45.5,
                default => null,
            };
        });
        $cartItem->method('getProductType')->willReturn('simple');

        $this->trackingMetaResolverMock->expects($this->once())
            ->method('getUid')
            ->with($cartItem)
            ->willReturn('18');

        $this->trackingMetaResolverMock->expects($this->once())
            ->method('getParentId')
            ->with($cartItem)
            ->willReturn('');

        $this->trackingMetaResolverMock->expects($this->once())
            ->method('getSku')
            ->with($cartItem)
            ->willReturn('standalone-sku');

        $result = $provider->get();

        $this->assertSame(
            array(
                'products' => array(
                    array(
                        'key' => '18::::18',
                        'uid' => '18',
                        'parentId' => '18',
                        'sku' => 'standalone-sku',
                        'qty' => 3.0,
                        'price' => 45.5,
                        'productType' => 'simple',
                    ),
                ),
            ),
            $result
        );
    }

    public function testGetReturnsEmptyProductsArrayWhenNoVisibleItems(): void
    {
        $provider = $this->createProvider();

        $quote = $this->createQuoteMock();
        $quote->method('getAllVisibleItems')->willReturn(array());

        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);

        $this->assertSame(array('products' => array()), $provider->get());
    }

    public function testGetLogsAndReturnsEmptyArrayWhenGetQuoteThrowsLocalizedException(): void
    {
        $provider = $this->createProvider();

        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willThrowException(new LocalizedException(__('Quote error')));

        $this->loggerMock->expects($this->once())
            ->method('error');

        $this->assertSame(array(), $provider->get());
    }

    public function testGetLogsAndReturnsEmptyArrayWhenUnexpectedExceptionOccurs(): void
    {
        $provider = $this->createProvider();

        $quote = $this->createQuoteMock();
        $quote->method('getAllVisibleItems')
            ->willThrowException(new \RuntimeException('Unexpected failure'));

        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);

        $this->loggerMock->expects($this->once())
            ->method('error');

        $this->assertSame(array(), $provider->get());
    }

    /**
     * @param array|null $outputOnRoutes
     * @return CartMetaProvider
     */
    private function createProvider(?array $outputOnRoutes = null): CartMetaProvider
    {
        if ($outputOnRoutes === null) {
            $this->requestMock->method('getModuleName')->willReturn('checkout');
            $this->requestMock->method('getControllerName')->willReturn('cart');
            $this->requestMock->method('getActionName')->willReturn('index');
        }

        return new CartMetaProvider(
            $this->checkoutSessionMock,
            $this->loggerMock,
            $this->trackingMetaResolverMock,
            $this->requestMock,
            $outputOnRoutes
        );
    }

    /**
     * @return Quote
     */
    private function createQuoteMock(): Quote
    {
        return $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array('getAllVisibleItems'))
            ->getMock();
    }

    /**
     * @return Item
     */
    private function createCartItemMock(): Item
    {
        return $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array('getQty', 'getDataUsingMethod', 'getProductType'))
            ->getMock();
    }
}
