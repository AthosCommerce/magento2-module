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
use AthosCommerce\Feed\Api\Data\MediaGallerySpecificationInterface;
use AthosCommerce\Feed\Model\Feed\Collection\MediaGalleryProcessor;

class MediaGalleryProcessorTest extends \PHPUnit\Framework\TestCase
{
    private $mediaGalleryProcessor;

    public function setUp(): void
    {
        $this->mediaGalleryProcessor = new MediaGalleryProcessor();
    }


    public function testProcessAfterLoad()
    {
        $mediaGallerySpecificationMock = $this->getMockForAbstractClass(MediaGallerySpecificationInterface::class);
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getMediaGallerySpecification')
            ->willReturn($mediaGallerySpecificationMock);
        $mediaGallerySpecificationMock->expects($this->once())
            ->method('getIncludeMediaGallery')
            ->willReturn(true);

        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->expects($this->once())
            ->method('addMediaGalleryData')
            ->willReturnSelf();

        $this->assertSame(
            $collectionMock,
            $this->mediaGalleryProcessor->processAfterLoad($collectionMock, $feedSpecificationMock)
        );
    }

    public function testProcessAfterFetchItems()
    {
        $mediaGallerySpecificationMock = $this->getMockForAbstractClass(MediaGallerySpecificationInterface::class);
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getMediaGallerySpecification')
            ->willReturn($mediaGallerySpecificationMock);
        $mediaGallerySpecificationMock->expects($this->once())
            ->method('getIncludeMediaGallery')
            ->willReturn(true);

        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->expects($this->once())
            ->method('setFlag')
            ->with('media_gallery_added', false)
            ->willReturnSelf();

        $this->assertSame(
            $collectionMock,
            $this->mediaGalleryProcessor->processAfterFetchItems($collectionMock, $feedSpecificationMock)
        );
    }
}
