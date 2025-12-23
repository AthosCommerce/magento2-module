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

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\Collection;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\CollectionFactory
    as ProductCollectionFactory;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\GetChildCollection;

class GetChildCollectionTest extends \PHPUnit\Framework\TestCase
{
    private $statusMock;

    private $productCollectionFactoryMock;

    private $getChildCollection;
    /**
     * @var AthosCommerceLogger
     */
    private $loggerMock;

    public function setUp(): void
    {
        $this->statusMock = $this->createMock(Status::class);
        $this->productCollectionFactoryMock = $this->createMock(ProductCollectionFactory::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);
        $this->getChildCollection = new GetChildCollection(
            $this->productCollectionFactoryMock,
            $this->statusMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $productCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($productCollectionMock);
        $productCollectionMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->withAnyParameters()
            ->willReturnSelf();
        $productCollectionMock->expects($this->once())
            ->method('addAttributeToFilter')
            ->withAnyParameters()
            ->willReturnSelf();
        $productCollectionMock->expects($this->once())
            ->method('addPriceData')
            ->willReturnSelf();

        $this->assertSame($productCollectionMock, $this->getChildCollection->execute([]));
    }
}
