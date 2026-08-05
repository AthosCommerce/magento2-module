<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Integration\ViewModel;

use AthosCommerce\Feed\ViewModel\GlobalScriptViewModel;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class GlobalScriptViewModelTest extends TestCase
{
    /**
     * @var GlobalScriptViewModel
     */
    private $viewModel;

    protected function setUp(): void
    {
        $this->viewModel = Bootstrap::getObjectManager()->get(GlobalScriptViewModel::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default_store athoscommerce/general/site_id site-athos-demo
     */
    public function testGetSiteIdReturnsConfiguredValue(): void
    {
        $this->assertSame('site-athos-demo', $this->viewModel->getSiteId());
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetSiteIdReturnsEmptyStringWhenValueIsMissing(): void
    {
        $this->assertSame('', $this->viewModel->getSiteId());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default_store athoscommerce/tracking/script_src https://cdn.dev-athoscommerce.com/tracking.js
     */
    public function testGetTrackingScriptSrcReturnsConfiguredValue(): void
    {
        $this->assertSame(
            'https://cdn.dev-athoscommerce.com/tracking.js',
            $this->viewModel->getTrackingScriptSrc()
        );
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetTrackingScriptSrcReturnsDefaultValueWhenConfigIsMissing(): void
    {
        $this->assertSame(
            'https://cdn.athoscommerce.net/analytics/beacon.js',
            $this->viewModel->getTrackingScriptSrc()
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default_store athoscommerce/general/site_id site-athos-demo
     */
    public function testShouldRenderReturnsTrueWhenSiteIdExistsAndDefaultScriptFallbackIsUsed(): void
    {
        $this->assertTrue($this->viewModel->shouldRender());
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testShouldRenderReturnsFalseWhenSiteIdIsMissing(): void
    {
        $this->assertFalse($this->viewModel->shouldRender());
    }
}
