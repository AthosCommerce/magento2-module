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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\Context;

use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\Context\StoreContextManager;

class StoreContextManagerTest extends \PHPUnit\Framework\TestCase
{
    private $storeManagerMock;

    private $emulationMock;

    private $storeContextManager;

    public function setUp(): void
    {
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->emulationMock = $this->createMock(Emulation::class);
        $this->storeContextManager = new StoreContextManager($this->storeManagerMock, $this->emulationMock);
    }

    public function testSetContextFromSpecification()
    {
        $storeMock = $this->createMock(Store::class);
        $storeId = 1;
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getStoreCode')
            ->willReturn('default');

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        $storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);
        $this->emulationMock->expects($this->once())
            ->method('startEnvironmentEmulation')
            ->with($storeId, Area::AREA_FRONTEND, true);

        $this->storeContextManager->setContextFromSpecification($feedSpecificationMock);
    }

    public function testResetContext()
    {
        $this->emulationMock->expects($this->once())
            ->method('stopEnvironmentEmulation')
            ->willReturnSelf();

        $this->storeContextManager->resetContext();
    }
}
