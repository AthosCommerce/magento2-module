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

declare(strict_types=1);

namespace Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product {
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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Configurable {

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\GetChildCollection;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\Collection;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\CollectionFactory as ProductCollectionFactory;

class GetChildCollectionTest extends \PHPUnit\Framework\TestCase
{
    private $statusMock;
    private $productCollectionFactory;
    private $getChildCollection;
    private $loggerMock;

    public function setUp(): void
    {
        $this->statusMock = $this->createMock(Status::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);
        $productCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addAttributeToSelect', 'addAttributeToFilter', 'addFieldToFilter', 'addPriceData'])
            ->getMock();
        $this->productCollectionFactory = new ProductCollectionFactory($productCollectionMock);
        $this->getChildCollection = new GetChildCollection(
            $this->productCollectionFactory,
            $this->statusMock,
            $this->loggerMock
        );
    }

    public function testExecute(): void
    {
        $productCollectionMock = $this->productCollectionFactory->create();

        $this->statusMock->expects($this->once())
            ->method('getVisibleStatusIds')
            ->willReturn([1, 2]);
        $productCollectionMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->with($this->callback(static function (array $attributes): bool {
                return in_array('status', $attributes, true);
            }))
            ->willReturnSelf();
        $productCollectionMock->expects($this->once())
            ->method('addAttributeToFilter')
            ->with('status', ['in' => [1, 2]])
            ->willReturnSelf();
        $productCollectionMock->expects($this->never())
            ->method('addFieldToFilter');
        $productCollectionMock->expects($this->once())
            ->method('addPriceData')
            ->willReturnSelf();

        $this->assertSame($productCollectionMock, $this->getChildCollection->execute([]));
    }
}
}
