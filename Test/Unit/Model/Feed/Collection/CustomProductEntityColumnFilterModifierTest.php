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

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\Collection\CustomProductEntityColumnFilterModifier;
use InvalidArgumentException;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

class CustomProductEntityColumnFilterModifierTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AthosCommerceLogger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var CustomProductEntityColumnFilterModifier
     */
    private $modifier;

    public function setUp(): void
    {
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);
        $this->modifier = new CustomProductEntityColumnFilterModifier($this->loggerMock);
    }

    public function testModifyNoopWhenFieldIsEmpty(): void
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getCustomProductEntityColumnField')
            ->willReturn(null);

        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->expects($this->never())->method('addFieldToFilter');
        $this->loggerMock->expects($this->never())->method('critical');

        $this->assertSame($collectionMock, $this->modifier->modify($collectionMock, $feedSpecificationMock));
    }

    public function testModifyAppliesStaticFieldFilter(): void
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getCustomProductEntityColumnField')
            ->willReturn('product_eligibility');
        $feedSpecificationMock->expects($this->once())
            ->method('getCustomProductEntityColumnOperator')
            ->willReturn('eq');
        $feedSpecificationMock->expects($this->once())
            ->method('getCustomProductEntityColumnValue')
            ->willReturn('1');

        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('e.product_eligibility', ['eq' => '1'])
            ->willReturnSelf();

        $this->loggerMock->expects($this->never())->method('critical');
        $this->assertSame($collectionMock, $this->modifier->modify($collectionMock, $feedSpecificationMock));
    }

    public function testModifyThrowsWhenFieldNameIsInvalid(): void
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getCustomProductEntityColumnField')
            ->willReturn('product_eligibility;drop');

        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->expects($this->never())->method('addFieldToFilter');

        $this->loggerMock->expects($this->once())->method('critical');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid custom product entity column field');
        $this->modifier->modify($collectionMock, $feedSpecificationMock);
    }

    public function testModifyThrowsWhenInListIsEmpty(): void
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getCustomProductEntityColumnField')
            ->willReturn('product_eligibility');
        $feedSpecificationMock->expects($this->once())
            ->method('getCustomProductEntityColumnOperator')
            ->willReturn('in');
        $feedSpecificationMock->expects($this->once())
            ->method('getCustomProductEntityColumnValue')
            ->willReturn([]);

        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->expects($this->never())->method('addFieldToFilter');

        $this->loggerMock->expects($this->once())->method('critical');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Custom product entity column value list is required for operator in');
        $this->modifier->modify($collectionMock, $feedSpecificationMock);
    }
}
