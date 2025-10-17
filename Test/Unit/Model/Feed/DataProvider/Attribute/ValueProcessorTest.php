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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Attribute;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Exception\LocalizedException;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\ValueProcessor;

class ValueProcessorTest extends \PHPUnit\Framework\TestCase
{
    private $valueProcessor;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->valueProcessor = new ValueProcessor();
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function testGetValue()
    {
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributeMock = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributeMock->expects($this->once())
            ->method('usesSource')
            ->willReturn(false);
        $attributeMock->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn('test');

        $this->assertSame(
            'test',
            $this->valueProcessor->getValue($attributeMock, 'test', $productMock)
        );
    }

    public function testGetValueOnCache()
    {
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $abstractSourceMock = $this->createMock(AbstractSource::class);
        $attributeMock = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributeMock->expects($this->once())
            ->method('usesSource')
            ->willReturn(true);
        $attributeMock->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn('test');
        $attributeMock->expects($this->once())
            ->method('getSource')
            ->willReturn($abstractSourceMock);
        $abstractSourceMock->expects($this->once())
            ->method('getOptionText')
            ->willReturn('test_option_text');

        $this->valueProcessor->getValue($attributeMock, 'test', $productMock);
        $this->valueProcessor->getValue($attributeMock, 'test', $productMock);
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function testGetValueException()
    {
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributeMock = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributeMock->expects($this->once())
            ->method('usesSource')
            ->willReturn(false);
        $attributeMock->expects($this->at(0))
            ->method('getAttributeCode')
            ->willReturn($attributeMock);
        $attributeMock->expects($this->at(1))
            ->method('getAttributeCode')
            ->willReturn('test');
        $attributeMock->expects($this->at(2))
            ->method('getAttributeCode')
            ->willReturn('test');

        $this->expectException(\Exception::class);

        $this->valueProcessor->getValue($attributeMock, $attributeMock, $productMock);
    }
}
