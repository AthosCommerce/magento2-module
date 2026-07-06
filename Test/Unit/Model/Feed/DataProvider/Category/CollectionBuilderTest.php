<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Category;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\Context\StoreContextManager;
use AthosCommerce\Feed\Model\Feed\DataProvider\Category\CollectionBuilder;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CollectionBuilderTest extends TestCase
{
    /**
     * @var CollectionFactory|MockObject
     */
    private $collectionFactoryMock;

    /**
     * @var StoreContextManager|MockObject
     */
    private $storeContextManagerMock;

    /**
     * @var AthosCommerceLogger|MockObject
     */
    private $loggerMock;

    /**
     * @var CollectionBuilder
     */
    private $collectionBuilder;

    protected function setUp(): void
    {
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->storeContextManagerMock = $this->createMock(StoreContextManager::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->collectionBuilder = new CollectionBuilder(
            $this->collectionFactoryMock,
            $this->storeContextManagerMock,
            $this->loggerMock
        );
    }

    public function testBuildCollection(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $storeMock = $this->createMock(Store::class);
        $selectMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['__toString'])
            ->getMock();
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);

        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);

        $feedSpecificationMock->expects($this->once())
            ->method('getStoreCode')
            ->willReturn('default');

        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeMenuCategories')
            ->willReturn(true);

        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeUrlHierarchy')
            ->willReturn(true);

        $this->storeContextManagerMock->expects($this->once())
            ->method('getStoreFromContext')
            ->willReturn($storeMock);

        $storeMock->expects($this->once())
            ->method('getRootCategoryId')
            ->willReturn(2);

        $collectionMock->expects($this->once())->method('setStore')->with('default')->willReturnSelf();
        $collectionMock->expects($this->once())->method('setStoreId')->with($storeMock)->willReturnSelf();
        $collectionMock->expects($this->once())->method('addUrlRewriteToResult')->willReturnSelf();
        $collectionMock->expects($this->once())->method('addAttributeToSelect')->willReturnSelf();
        $collectionMock->expects($this->exactly(3))->method('addAttributeToFilter')->willReturnSelf();
        $collectionMock->expects($this->once())->method('getSelect')->willReturn($selectMock);

        $selectMock->expects($this->once())
            ->method('__toString')
            ->willReturn('SELECT ...');

        $this->loggerMock->expects($this->once())
            ->method('debug');

        $result = $this->collectionBuilder->buildCollection([1, 2, 3], $feedSpecificationMock);

        $this->assertSame($collectionMock, $result);
    }
}
