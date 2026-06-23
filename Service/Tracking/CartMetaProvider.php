<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Service\Tracking;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;

class CartMetaProvider
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @var CartItemIdentityResolver
     */
    private $trackingMetaResolver;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var string[]|null
     */
    private $outputOnRoutes;

    /**
     * @param CheckoutSession $checkoutSession
     * @param AthosCommerceLogger $logger
     * @param CartItemIdentityResolver $trackingMetaResolver
     * @param RequestInterface $request
     * @param string[]|null $outputOnRoutes
     */
    public function __construct(
        CheckoutSession          $checkoutSession,
        AthosCommerceLogger      $logger,
        CartItemIdentityResolver $trackingMetaResolver,
        RequestInterface         $request,
        array                    $outputOnRoutes = null
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->trackingMetaResolver = $trackingMetaResolver;
        $this->request = $request;
        $this->outputOnRoutes = $outputOnRoutes === null
            ? null
            : array_map('strval', $outputOnRoutes);
    }

    /**
     * @return array
     */
    public function get(): array
    {
        $return = array();

        if (!$this->shouldOutput()) {
            return $return;
        }

        try {
            $quote = $this->getQuote();
            if ($quote !== null) {
                $return['products'] = array_values($this->cartItems($quote));
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                'Exception while getting quote:',
                array(
                    'method' => __METHOD__,
                    'message' => $exception->getMessage(),
                    'exception' => $exception,
                )
            );
        }

        return $return;
    }

    /**
     * @return CartInterface|null
     */
    private function getQuote(): ?CartInterface
    {
        $return = null;

        try {
            $return = $this->checkoutSession->getQuote();
        } catch (LocalizedException $exception) {
            $this->logger->error(
                'Exception while getting quote:',
                array(
                    'method' => __METHOD__,
                    'message' => $exception->getMessage(),
                    'exception' => $exception,
                )
            );
        }

        return $return;
    }

    /**
     * @param CartInterface $cart
     * @return array
     */
    private function cartItems(CartInterface $cart): array
    {
        $cartItems = method_exists($cart, 'getAllVisibleItems')
            ? $cart->getAllVisibleItems()
            : array();

        return array_filter(array_map(array($this, 'getMetaForCartItem'), $cartItems));
    }

    /**
     * @param CartItemInterface $cartItem
     * @return array
     */
    private function getMetaForCartItem(CartItemInterface $cartItem): array
    {
        $uid = (string)($this->trackingMetaResolver->getUid($cartItem) ?: '');
        $parentId = $this->trackingMetaResolver->getParentId($cartItem);
        $parentId = $parentId !== null && $parentId !== '' ? (string)$parentId : $uid;
        $sku = (string)$this->trackingMetaResolver->getSku($cartItem);

        return array(
            'key' => $this->buildItemKey($uid, $parentId),
            'uid' => $uid,
            'parentId' => $parentId,
            'sku' => $sku,
            'qty' => (float)$cartItem->getDataUsingMethod('qty'),
            'price' => $this->getItemPrice($cartItem),
            'productType' => (string)$cartItem->getProductType(),
        );
    }

    /**
     * @param CartItemInterface $cartItem
     * @return float
     */
    private function getItemPrice(CartItemInterface $cartItem): float
    {
        $price = (float)$cartItem->getDataUsingMethod('price');
        return $price > 0 ? $price : 0.0;
    }

    /**
     * @param string $uid
     * @param string $parentId
     * @return string
     */
    private function buildItemKey(string $uid, string $parentId): string
    {
        return $uid . '::' . $parentId;
    }

    /**
     * @return bool
     */
    private function shouldOutput(): bool
    {
        if ($this->outputOnRoutes === null) {
            return true;
        }

        $return = false;
        $currentRoute = implode(
            '_',
            array(
                $this->request->getModuleName(),
                $this->request->getControllerName(),
                $this->request->getActionName()
            )
        );

        foreach ($this->outputOnRoutes as $route) {
            if ($currentRoute === $route) {
                $return = true;
                break;
            }
        }

        return $return;
    }
}
