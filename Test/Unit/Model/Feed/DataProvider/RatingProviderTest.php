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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\DB\Select;
use Magento\Review\Model\Review\Summary;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use Magento\Review\Model\ResourceModel\Review\Summary\Collection as SummaryCollection;
use Magento\Review\Model\ResourceModel\Review\Summary\CollectionFactory as SummaryCollectionFactory;
use AthosCommerce\Feed\Model\Feed\DataProvider\RatingProvider;

class RatingProviderTest extends \PHPUnit\Framework\TestCase
{
    private $collectionFactoryMock;

    private $storeManagerMock;

    private $ratingProvider;

    public function setUp(): void
    {
        $this->collectionFactoryMock = $this->createMock(SummaryCollectionFactory::class);
        $this->storeManagerMock = $this->createMock(StoreManager::class);
        $this->ratingProvider = new RatingProvider(
            $this->collectionFactoryMock,
            $this->storeManagerMock
        );
    }

    public function testGetData()
    {
        $summaryMock = $this->createMock(Summary::class);
        $abstractDbMock = $this->createMock(AbstractDb::class);
        $selectMock = $this->createMock(Select::class);
        $collectionMock = $this->createMock(SummaryCollection::class);
        $storeMock = $this->createMock(Store::class);
        $storeId = 1;
        $products = [
            [
                'entity_id' => 1
            ]
        ];
        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getStoreCode')
            ->willReturn('default');

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        $storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);
        $collectionMock->expects($this->once())
            ->method('getSelect')
            ->willReturn($selectMock);
        $collectionMock->expects($this->once())
            ->method('getResource')
            ->willReturn($abstractDbMock);
        $abstractDbMock->expects($this->once())
            ->method('getTable')
            ->with('review_entity')
            ->willReturn('review_entity');
        $selectMock->expects($this->once())
            ->method('joinLeft')
            ->withAnyParameters()
            ->willReturnSelf();
        $selectMock->expects($this->any())
            ->method('where')
            ->withAnyParameters()
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$summaryMock]);
        $summaryMock->expects($this->once())
            ->method('getEntityPkValue')
            ->willReturn(1);
        $summaryMock->expects($this->once())
            ->method('getRatingSummary')
            ->willReturn(80);
        $summaryMock->expects($this->once())
            ->method('getReviewsCount')
            ->willReturn(10);

        $this->assertSame(
            [
                [
                    'entity_id' => 1,
                    'rating' => 4.0,
                    'rating_count' => 10
                ]
            ],
            $this->ratingProvider->getData($products, $feedSpecificationMock)
        );
    }
}
