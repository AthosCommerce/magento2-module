<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Stock;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\LegacyStockProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\MsiStockProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\MsiStockResolver;
use Magento\Framework\Module\Manager;
use PHPUnit\Framework\TestCase;

class MsiStockResolverTest extends TestCase
{
    private $moduleList = [
        'Magento_InventoryReservationsApi',
        'Magento_InventorySalesApi',
        'Magento_InventoryCatalogApi',
    ];

    private $moduleManagerMock;
    private $msiStockProviderMock;
    private $legacyStockProviderMock;
    private $loggerMock;
    private $msiStockResolver;

    protected function setUp(): void
    {
        $this->moduleManagerMock = $this->createMock(Manager::class);
        $this->msiStockProviderMock = $this->createMock(MsiStockProvider::class);
        $this->legacyStockProviderMock = $this->createMock(LegacyStockProvider::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->msiStockResolver = new MsiStockResolver(
            $this->moduleManagerMock,
            $this->msiStockProviderMock,
            $this->legacyStockProviderMock,
            $this->loggerMock,
            $this->moduleList
        );
    }

    public function testResolveWithDisabledMsiPayloadReturnsLegacyProvider(): void
    {
        $this->moduleManagerMock->expects($this->never())->method('isEnabled');

        $this->assertSame($this->legacyStockProviderMock, $this->msiStockResolver->resolve(false));
    }

    public function testResolveWithEnabledMsiPayloadChecksModules(): void
    {
        $this->moduleManagerMock->expects($this->exactly(3))
            ->method('isEnabled')
            ->willReturnMap([
                [$this->moduleList[0], true],
                [$this->moduleList[1], true],
                [$this->moduleList[2], true],
            ]);

        $this->assertSame($this->msiStockProviderMock, $this->msiStockResolver->resolve(true));
    }
}
