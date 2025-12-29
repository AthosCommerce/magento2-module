<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\ViewModel;

use AthosCommerce\Feed\ViewModel\PdpViewModel;
use AthosCommerce\Feed\Service\Config;
use AthosCommerce\Feed\Api\ConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PdpViewModelTest extends TestCase
{
    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var PdpViewModel
     */
    private $viewModel;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->viewModel = new PdpViewModel($this->configMock);
    }

    public function testReturnsSiteIdFromConfig(): void
    {
        $this->configMock
            ->expects($this->once())
            ->method('getSiteId')
            ->willReturn('SITE-123');

        $this->assertSame(
            'SITE-123',
            $this->viewModel->getAthoscommerceSiteId()
        );
    }

    public function testReturnsEmptyStringWhenConfigReturnsNull(): void
    {
        $this->configMock
            ->expects($this->once())
            ->method('getSiteId')
            ->willReturn(null);

        $this->assertSame(
            '',
            $this->viewModel->getAthoscommerceSiteId()
        );
    }

    public function testReturnsEmptyStringWhenConfigReturnsEmptyValue(): void
    {
        $this->configMock
            ->expects($this->once())
            ->method('getSiteId')
            ->willReturn('');
        
        $this->assertSame(
            '',
            $this->viewModel->getAthoscommerceSiteId()
        );
    }
}
