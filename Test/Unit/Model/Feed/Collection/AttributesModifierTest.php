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

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\Collection\AttributesModifier;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\AttributesProviderInterface;

class AttributesModifierTest extends \PHPUnit\Framework\TestCase
{
    private $attributesProviderMock;

    private $attributesModifier;

    public function setUp(): void
    {
        $this->attributesProviderMock = $this->createMock(AttributesProviderInterface::class);
        $this->attributesModifier = new AttributesModifier(
            $this->attributesProviderMock,
            []
        );
    }

    public function testModify()
    {
        $productAttributesMock = $this->createMock(ProductAttributeInterface::class);
        $attributes = [
            $productAttributesMock
        ];
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $collectionMock = $this->createMock(Collection::class);
        $this->attributesProviderMock->expects($this->once())
            ->method('getAttributes')
            ->with($feedSpecificationMock)
            ->willReturn($attributes);
        $productAttributesMock->expects($this->once())
            ->method('getAttributeCode')
            ->willReturn('code');

        $collectionMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->with(['code'])
            ->willReturnSelf();

        $this->assertSame(
            $collectionMock,
            $this->attributesModifier->modify($collectionMock, $feedSpecificationMock)
        );
    }
}
