<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\ViewModel;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Service\Config;
use AthosCommerce\Feed\Service\Tracking\OrderDataResolverInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Api\Data\OrderInterface;

class CheckoutSuccessViewModel implements ArgumentInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @var OrderDataResolverInterface
     */
    private $orderDataResolver;

    /**
     * @param Config $config
     * @param Session $checkoutSession
     * @param SerializerInterface $serializer
     * @param AthosCommerceLogger $logger
     * @param OrderDataResolverInterface $orderDataResolver
     */
    public function __construct(
        Config                     $config,
        Session                    $checkoutSession,
        SerializerInterface        $serializer,
        AthosCommerceLogger        $logger,
        OrderDataResolverInterface $orderDataResolver
    )
    {
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->orderDataResolver = $orderDataResolver;
    }

    /**
     * @return OrderInterface|null
     */
    private function getOrder(): ?OrderInterface
    {
        $order = $this->checkoutSession->getLastRealOrder();

        if (!$order || $order->getId() === null) {
            return null;
        }

        return $order;
    }

    /**
     * @return string
     */
    public function getSuccessPageConfig(): string
    {
        if (true !== $this->config->shouldRender()) {
            return $this->serializer->serialize([]);
        }

        $order = $this->getOrder();

        if (!$order) {
            return $this->serializer->serialize([]);
        }

        try {
            return $this->serializer->serialize($this->orderDataResolver->resolve($order));
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Failed to resolve Athos Commerce checkout success payload',
                ['exception' => $exception]
            );

            return $this->serializer->serialize([]);
        }
    }
}
