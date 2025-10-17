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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Configurable;

use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute\Collection;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute\CollectionFactory
    as AttributeCollectionFactory;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\GetAttributesCollection;

class GetAttributesCollectionTest extends \PHPUnit\Framework\TestCase
{
    private $joinProcessorMock;

    private $attributeCollectionFactoryMock;

    private $getAttributesCollection;

    public function setUp(): void
    {
        $this->joinProcessorMock = $this->createMock(JoinProcessorInterface::class);
        $this->attributeCollectionFactoryMock = $this->createMock(AttributeCollectionFactory::class);
        $this->getAttributesCollection = new GetAttributesCollection(
            $this->joinProcessorMock,
            $this->attributeCollectionFactoryMock
        );
    }

    public function testExecute()
    {
        $attributesCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->attributeCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($attributesCollectionMock);
        $this->joinProcessorMock->expects($this->once())
            ->method('process')
            ->with($attributesCollectionMock);
        $attributesCollectionMock->expects($this->once())
            ->method('orderByPosition')
            ->willReturnSelf();

        $this->assertSame($attributesCollectionMock, $this->getAttributesCollection->execute([]));
    }
}
