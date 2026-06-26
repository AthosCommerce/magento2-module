<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Integration\Service;

use AthosCommerce\Feed\Service\Config;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    private $config;

    protected function setUp(): void
    {
        $this->config = Bootstrap::getObjectManager()->get(Config::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default_store athoscommerce/general/site_id site-athos-demo
     */
    public function testGetSiteIdReturnsConfiguredValue(): void
    {
        $this->assertSame(
            'site-athos-demo',
            $this->config->getSiteId()
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default_store athoscommerce/tracking/script_src https://cdn.dev-athoscommerce.com/tracking.js
     */
    public function testGetTrackingScriptSrcReturnsConfiguredValue(): void
    {
        $this->assertSame(
            'https://cdn.dev-athoscommerce.com/tracking.js',
            $this->config->getTrackingScriptSrc()
        );
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetTrackingScriptSrcReturnsDefaultWhenConfigIsEmpty(): void
    {
        $this->assertSame(
            'https://cdn.athoscommerce.net/analytics/beacon.js',
            $this->config->getTrackingScriptSrc()
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default_store athoscommerce/general/site_id site-athos-demo
     * @magentoConfigFixture default_store athoscommerce/tracking/script_src https://cdn-dev-athoscommerce.com/tracking.js
     */
    public function testShouldRenderReturnsTrueWhenSiteIdAndTrackingScriptAreAvailable(): void
    {
        $this->assertTrue($this->config->shouldRender());
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testShouldRenderReturnsFalseWhenSiteIdIsMissing(): void
    {
        $this->assertFalse($this->config->shouldRender());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default_store athoscommerce/general/site_id site-athos-demo
     */
    public function testShouldRenderReturnsTrueWhenSiteIdExistsAndDefaultTrackingScriptIsUsed(): void
    {
        $this->assertTrue($this->config->shouldRender());
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetSiteIdReturnsNullWhenValueIsNotConfigured(): void
    {
        $this->assertNull($this->config->getSiteId());
    }
}
