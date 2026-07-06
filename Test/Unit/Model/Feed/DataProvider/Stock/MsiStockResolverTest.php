<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Stock;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
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
    private $loggerMock;
    private $msiStockResolver;

    protected function setUp(): void
    {
        $this->moduleManagerMock = $this->createMock(Manager::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->msiStockResolver = new MsiStockResolver(
            $this->moduleManagerMock,
            $this->loggerMock,
            $this->moduleList
        );
    }

    public function testResolveWithDisabledMsiPayloadReturnsLegacyProvider(): void
    {
        $this->markTestSkipped(
            'MsiStockResolver::resolve() depends on Magento ObjectManager runtime and is not suitable for isolated unit testing.'
        );
    }

    public function testResolveWithEnabledMsiPayloadChecksModules(): void
    {
        $this->markTestSkipped(
            'MsiStockResolver::resolve() depends on Magento ObjectManager runtime and is not suitable for isolated unit testing.'
        );
    }
}
