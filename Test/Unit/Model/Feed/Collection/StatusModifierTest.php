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

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\Collection\StatusModifier;

class StatusModifierTest extends \PHPUnit\Framework\TestCase
{
    private $statusMock;

    private $statusModifier;

    public function setUp(): void
    {
        $this->statusMock = $this->createMock(Status::class);
        $this->statusModifier = new StatusModifier($this->statusMock);
    }

    public function testModify()
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $visibilityResults = [1];
        $this->statusMock->expects($this->once())
            ->method('getVisibleStatusIds')
            ->willReturn($visibilityResults);
        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->with(ProductInterface::STATUS)
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('addAttributeToFilter')
            ->with(ProductInterface::STATUS, ['in' => $visibilityResults])
            ->willReturnSelf();

        $this->assertSame($collectionMock, $this->statusModifier->modify($collectionMock, $feedSpecificationMock));
    }
}
