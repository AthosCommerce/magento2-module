<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\ViewModel;

use AthosCommerce\Feed\Service\Config;
use AthosCommerce\Feed\ViewModel\GlobalScriptViewModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GlobalScriptViewModelTest extends TestCase
{
    /**
     * @var Config
     */
    private $configMock;

    /**
     * @var GlobalScriptViewModel
     */
    private $viewModel;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);

        $this->viewModel = new GlobalScriptViewModel(
            $this->configMock
        );
    }

    public function testReturnsSiteId(): void
    {
        $this->configMock
            ->expects($this->once())
            ->method('getSiteId')
            ->willReturn('site-althos-demo');

        $this->assertSame(
            'site-althos-demo',
            $this->viewModel->getSiteId()
        );
    }
    
    public function testReturnsEmptyStringWhenSiteIdIsNull(): void
    {
        $this->configMock
            ->expects($this->once())
            ->method('getSiteId')
            ->willReturn(null);

        $this->assertSame(
            '',
            $this->viewModel->getSiteId()
        );
    }

    public function testReturnsEmptyStringWhenSiteIdIsEmpty(): void
    {
        $this->configMock
            ->expects($this->once())
            ->method('getSiteId')
            ->willReturn('');

        $this->assertSame(
            '',
            $this->viewModel->getSiteId()
        );
    }

    public function testReturnsTrackingScriptSource(): void
    {
        $this->configMock
            ->expects($this->once())
            ->method('getTrackingScriptSrc')
            ->willReturn(
                'https://cdn.dev-athoscommerce.com/tracking.js'
            );

        $this->assertSame(
            'https://cdn.dev-athoscommerce.com/tracking.js',
            $this->viewModel->getTrackingScriptSrc()
        );
    }

    public function testReturnsTrueWhenRenderingEnabled(): void
    {
        $this->configMock
            ->expects($this->once())
            ->method('shouldRender')
            ->willReturn(true);

        $this->assertTrue(
            $this->viewModel->shouldRender()
        );
    }

    public function testReturnsFalseWhenRenderingDisabled(): void
    {
        $this->configMock
            ->expects($this->once())
            ->method('shouldRender')
            ->willReturn(false);

        $this->assertFalse(
            $this->viewModel->shouldRender()
        );
    }
}
