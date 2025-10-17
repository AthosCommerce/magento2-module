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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\Collection;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Status;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\Collection\StockModifier;

class StockModifierTest extends \PHPUnit\Framework\TestCase
{
    private $statusMock;

    private $stockModifier;

    public function setUp(): void
    {
        $this->statusMock = $this->createMock(Status::class);
        $this->stockModifier = new StockModifier($this->statusMock);
    }

    public function testModify()
    {
        $includeOutOfStock = false;
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeOutOfStock')
            ->willReturn($includeOutOfStock);
        $stockFlag = 'has_stock_status_filter';
        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->expects($this->once())
            ->method('hasFlag')
            ->with($stockFlag)
            ->willReturn(false);
        $this->statusMock->expects($this->once())
            ->method('addStockDataToCollection')
            ->with($collectionMock, !$includeOutOfStock)
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('setFlag')
            ->with($stockFlag, true)
            ->willReturnSelf();

        $this->assertSame($collectionMock, $this->stockModifier->modify($collectionMock, $feedSpecificationMock));
    }
}
