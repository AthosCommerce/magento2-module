<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Stock;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\MsiStockProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\MsiStockResolver;

class MsiStockResolverTest extends \PHPUnit\Framework\TestCase
{
    private $moduleList = [
        'Magento_InventoryReservationsApi',
        'Magento_InventorySalesApi',
        'Magento_InventoryCatalogApi'
    ];

    private $moduleManagerMock;

    private $msiStockResolver;

    /**
     * @var MsiStockProvider
     */
    protected $stockProviderMock;

    public function setUp(): void
    {
        $this->moduleManagerMock = $this->createMock(Manager::class);
        $this->stockProviderMock = $this->createMock(MsiStockProvider::class);
        $this->msiStockResolver = new MsiStockResolver($this->moduleManagerMock,  $this->stockProviderMock ,$this->moduleList);
    }

    public function testResolve()
    {
        $this->moduleManagerMock->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);

        $this->msiStockResolver->resolve();
    }

    public function testResolveExceptionCase()
    {
        $this->moduleManagerMock->expects($this->any())
            ->method('isEnabled')
            ->willReturn(false);
        $this->expectException(NoSuchEntityException::class);

        $this->msiStockResolver->resolve();
    }
}
