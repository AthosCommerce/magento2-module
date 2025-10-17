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

use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\ChildAttributesProvider;
use AthosCommerce\Feed\Model\Feed\Specification\Feed;

class ChildAttributesProviderTest extends \PHPUnit\Framework\TestCase
{
    private $eavConfigMock;

    private $childAttributesProvider;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->eavConfigMock = $this->createMock(Config::class);
        $this->childAttributesProvider = new ChildAttributesProvider($this->eavConfigMock);
    }

    /**
     * @return void
     */
    public function testGetAttributes()
    {
        $abstractAttributeMock = $this->getMockBuilder(AbstractAttribute::class)
            ->disableOriginalConstructor()
            ->getMock();
        $feedSpecificationMock = $this->getMockBuilder(Feed::class)
            ->disableOriginalConstructor()
            ->getMock();
        $feedSpecificationMock->expects($this->once())
            ->method('getChildFields')
            ->willReturn(['test']);
        $this->eavConfigMock->expects($this->any())
            ->method('getAttribute')
            ->withAnyParameters()
            ->willReturn($abstractAttributeMock);
        $abstractAttributeMock->expects($this->any())
            ->method('getAttributeId')
            ->willReturn(0);

        $this->assertSame(
            [$abstractAttributeMock],
            $this->childAttributesProvider->getAttributes($feedSpecificationMock)
        );
    }
}
