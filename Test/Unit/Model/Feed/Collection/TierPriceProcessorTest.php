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
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\Collection\TierPriceProcessor;

class TierPriceProcessorTest extends \PHPUnit\Framework\TestCase
{
    private $tierPriceProcessor;

    public function setUp(): void
    {
        $this->tierPriceProcessor = new TierPriceProcessor();
    }

    public function testProcessAfterLoad()
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeTierPricing')
            ->willReturn(true);
        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->expects($this->once())
            ->method('addTierPriceData')
            ->willReturnSelf();

        $this->assertSame(
            $collectionMock,
            $this->tierPriceProcessor->processAfterLoad($collectionMock, $feedSpecificationMock)
        );
    }

    public function testProcessAfterFetchItems()
    {
        $collectionMock = $this->createMock(Collection::class);
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeTierPricing')
            ->willReturn(true);
        $collectionMock->expects($this->once())
            ->method('setFlag')
            ->with('tier_price_added', false);

        $this->assertSame(
            $collectionMock,
            $this->tierPriceProcessor->processAfterFetchItems($collectionMock, $feedSpecificationMock)
        );
    }
}
