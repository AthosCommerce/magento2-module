<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use AthosCommerce\Feed\Service\Config;

class GlobalScriptViewModel implements ArgumentInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    )
    {
        $this->config = $config;
    }

    /**
     * @return string|null
     */
    public function getSiteId(): ?string
    {
        return (string)$this->config->getSiteId();
    }

    /**
     * @return string
     */
    public function getTrackingScriptSrc(): string
    {
        return $this->config->getTrackingScriptSrc();
    }

    /**
     * @return bool
     */
    public function shouldRender(): bool
    {
        return $this->getSiteId() && $this->getScriptSrc();
    }
}
