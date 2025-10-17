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

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogRule\Model\ResourceModel\Product\CollectionProcessor;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\Collection\PricesModifier;

class PricesModifierTest extends \PHPUnit\Framework\TestCase
{
    private $collectionProcessorMock;

    private $pricesModifier;

    public function setUp(): void
    {
        $this->collectionProcessorMock = $this->createMock(CollectionProcessor::class);
        $this->pricesModifier = new PricesModifier(
            $this->collectionProcessorMock
        );
    }

    public function testModify()
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn([]);
        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->expects($this->once())
            ->method('addPriceData')
            ->willReturnSelf();
        $this->collectionProcessorMock->expects($this->once())
            ->method('addPriceData')
            ->with($collectionMock)
            ->willReturnSelf();

        $this->assertSame($collectionMock, $this->pricesModifier->modify($collectionMock, $feedSpecificationMock));
    }
}
