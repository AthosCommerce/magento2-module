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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Category;

use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use AthosCommerce\Feed\Model\Feed\DataProvider\Category\CollectionBuilder;
use AthosCommerce\Feed\Model\Feed\Specification\Feed;

class CollectionBuilderTest extends \PHPUnit\Framework\TestCase
{
    private $collectionFactoryMock;

    private $collectionBuilder;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->collectionBuilder = new CollectionBuilder($this->collectionFactoryMock);
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function testBuildCollection()
    {
        $storeCode = 'default';

        $feedSpecificationMock = $this->getMockBuilder(Feed::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);
        $feedSpecificationMock->expects($this->once())
            ->method('getStoreCode')
            ->willReturn($storeCode);
        $collectionMock->expects($this->once())
            ->method('setStore')
            ->with($storeCode);
        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeMenuCategories')
            ->willReturn(false);
        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeUrlHierarchy')
            ->willReturn(false);
        $collectionMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->withAnyParameters();
        $collectionMock->expects($this->any())
            ->method('addAttributeToFilter')
            ->withAnyParameters()
            ->willReturnSelf();

        $this->assertSame(
            $collectionMock,
            $this->collectionBuilder->buildCollection([], $feedSpecificationMock)
        );
    }
}
