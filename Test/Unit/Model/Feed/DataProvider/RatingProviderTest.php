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

namespace Magento\Review\Model\ResourceModel\Review\Summary {
    if (!class_exists(CollectionFactory::class, false)) {
        class CollectionFactory
        {
            private $collection;

            public function __construct($collection = null)
            {
                $this->collection = $collection;
            }

            public function create()
            {
                return $this->collection;
            }
        }
    }
}

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider {

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\DataProvider\RatingProvider;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Review\Model\ResourceModel\Review\Summary\Collection as SummaryCollection;
use Magento\Review\Model\ResourceModel\Review\Summary\CollectionFactory as SummaryCollectionFactory;
use Magento\Review\Model\Review\Summary;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class RatingProviderTest extends TestCase
{
    private $collectionFactoryMock;
    private $storeManagerMock;
    private $parentVariantResolverMock;
    private $loggerMock;
    private $collectionMock;
    private $ratingProvider;

    protected function setUp(): void
    {
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->parentVariantResolverMock = $this->createMock(ParentVariantResolver::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);
        $this->collectionMock = $this->createMock(SummaryCollection::class);
        $this->collectionFactoryMock = new SummaryCollectionFactory($this->collectionMock);

        $this->ratingProvider = new RatingProvider(
            $this->collectionFactoryMock,
            $this->storeManagerMock,
            $this->parentVariantResolverMock,
            $this->loggerMock
        );
    }

    public function testGetData(): void
    {
        $summaryMock = $this->createMock(Summary::class);
        $abstractDbMock = $this->createMock(AbstractDb::class);
        $selectMock = $this->createMock(Select::class);
        $storeMock = $this->createMock(Store::class);

        $products = [
            [
                'entity_id' => 1,
            ],
        ];

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn([]);

        $feedSpecificationMock->expects($this->once())
            ->method('getStoreCode')
            ->willReturn('default');

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->with('default')
            ->willReturn($storeMock);

        $storeMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->collectionMock->expects($this->once())
            ->method('addStoreFilter')
            ->with(1)
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
            ->method('getSelect')
            ->willReturn($selectMock);

        $this->collectionMock->expects($this->once())
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

        $selectMock->expects($this->exactly(2))
            ->method('where')
            ->withAnyParameters()
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
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
                    'rating_count' => 10,
                ],
            ],
            $this->ratingProvider->getData($products, $feedSpecificationMock)
        );
    }
}
}
