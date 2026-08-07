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
use AthosCommerce\Feed\Model\Feed\Collection\SelectionFilterModifier;
use InvalidArgumentException;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

class SelectionFilterModifierTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AthosCommerceLogger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var SelectionFilterModifier
     */
    private $selectionFilterModifier;

    public function setUp(): void
    {
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);
        $this->selectionFilterModifier = new SelectionFilterModifier($this->loggerMock);
    }

    public function testModifyNoopWhenDisabled(): void
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getEnableCriteriaFilter')
            ->willReturn(false);

        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->expects($this->never())->method('addAttributeToFilter');
        $this->loggerMock->expects($this->never())->method('critical');

        $this->assertSame($collectionMock, $this->selectionFilterModifier->modify($collectionMock, $feedSpecificationMock));
    }

    /**
     * @dataProvider supportedOperatorDataProvider
     */
    public function testModifyAppliesExpectedFilterForSupportedOperators(string $operator, $inputValue, $expectedValue): void
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getEnableCriteriaFilter')
            ->willReturn(true);
        $feedSpecificationMock->expects($this->once())
            ->method('getCriteriaField')
            ->willReturn('price');
        $feedSpecificationMock->expects($this->once())
            ->method('getCriteriaOperator')
            ->willReturn($operator);
        $feedSpecificationMock->expects($this->once())
            ->method('getCriteriaValue')
            ->willReturn($inputValue);

        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->expects($this->once())
            ->method('addAttributeToFilter')
            ->with('price', [$operator => $expectedValue])
            ->willReturnSelf();
        $this->loggerMock->expects($this->never())->method('critical');

        $this->assertSame($collectionMock, $this->selectionFilterModifier->modify($collectionMock, $feedSpecificationMock));
    }

    public static function supportedOperatorDataProvider(): array
    {
        return [
            ['eq', '10', '10'],
            ['neq', '10', '10'],
            ['gt', '10', '10'],
            ['gteq', '10', '10'],
            ['lt', '10', '10'],
            ['lteq', '10', '10'],
            ['in', '10', ['10']],
            ['nin', '10', ['10']],
        ];
    }

    public function testModifyThrowsForUnsupportedOperator(): void
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getEnableCriteriaFilter')
            ->willReturn(true);
        $feedSpecificationMock->expects($this->once())
            ->method('getCriteriaField')
            ->willReturn('product_eligibility');
        $feedSpecificationMock->expects($this->once())
            ->method('getCriteriaOperator')
            ->willReturn('foo');
        $feedSpecificationMock->expects($this->once())
            ->method('getCriteriaValue')
            ->willReturn('1');

        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->expects($this->never())->method('addAttributeToFilter');

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with(
                'Unsupported criteria operator:',
                $this->arrayHasKey('operator')
            );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported criteria operator: foo');
        $this->selectionFilterModifier->modify($collectionMock, $feedSpecificationMock);
    }

    public function testModifyThrowsWhenValueIsMissing(): void
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getEnableCriteriaFilter')
            ->willReturn(true);
        $feedSpecificationMock->expects($this->once())
            ->method('getCriteriaField')
            ->willReturn('product_eligibility');
        $feedSpecificationMock->expects($this->once())
            ->method('getCriteriaOperator')
            ->willReturn('eq');
        $feedSpecificationMock->expects($this->once())
            ->method('getCriteriaValue')
            ->willReturn(null);

        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->expects($this->never())->method('addAttributeToFilter');

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with(
                'Criteria value is required for operator:',
                $this->arrayHasKey('operator')
            );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Criteria value is required for operator eq');
        $this->selectionFilterModifier->modify($collectionMock, $feedSpecificationMock);
    }

    /**
     * @dataProvider emptyInNinValuesProvider
     */
    public function testModifyThrowsWhenInNinValueListIsEmpty(string $operator): void
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getEnableCriteriaFilter')
            ->willReturn(true);
        $feedSpecificationMock->expects($this->once())
            ->method('getCriteriaField')
            ->willReturn('product_eligibility');
        $feedSpecificationMock->expects($this->once())
            ->method('getCriteriaOperator')
            ->willReturn($operator);
        $feedSpecificationMock->expects($this->once())
            ->method('getCriteriaValue')
            ->willReturn([]);

        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->expects($this->never())->method('addAttributeToFilter');

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with(
                'Criteria value list is required for operator:',
                $this->arrayHasKey('operator')
            );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Criteria value list is required for operator %s', $operator));
        $this->selectionFilterModifier->modify($collectionMock, $feedSpecificationMock);
    }

    public static function emptyInNinValuesProvider(): array
    {
        return [
            ['in'],
            ['nin'],
        ];
    }
}
