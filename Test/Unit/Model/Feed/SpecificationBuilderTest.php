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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterfaceFactory;
use AthosCommerce\Feed\Api\Data\MediaGallerySpecificationInterface;
use AthosCommerce\Feed\Api\Data\MediaGallerySpecificationInterfaceFactory;
use AthosCommerce\Feed\Api\MetadataInterface;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilder;

class SpecificationBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FeedSpecificationInterfaceFactory
     */
    private $feedSpecificationFactoryMock;

    /**
     * @var MediaGallerySpecificationInterfaceFactory
     */
    private $mediaGallerySpecificationFactoryMock;

    private $specificationBuilder;

    /**
     * @var array
     */
    private $defaultValues = [
        'feed' => [
            FeedSpecificationInterface::STORE_CODE => 'default',
            FeedSpecificationInterface::HIERARCHY_SEPARATOR => '/',
            FeedSpecificationInterface::MULTI_VALUED_SEPARATOR => '|',
            FeedSpecificationInterface::INCLUDE_URL_HIERARCHY => false,
            FeedSpecificationInterface::INCLUDE_MENU_CATEGORIES => false,
            FeedSpecificationInterface::INCLUDE_JSON_CONFIG => false,
            FeedSpecificationInterface::INCLUDE_CHILD_PRICES => false,
            FeedSpecificationInterface::INCLUDE_TIER_PRICES => false,
            FeedSpecificationInterface::CUSTOMER_ID => null,
            FeedSpecificationInterface::CHILD_FIELDS => [],
            FeedSpecificationInterface::INCLUDE_OUT_OF_STOCK => false,
            FeedSpecificationInterface::IGNORE_FIELDS => [],
            FeedSpecificationInterface::FORMAT => MetadataInterface::FORMAT_JSON,
        ],
        'media_gallery' => [
            MediaGallerySpecificationInterface::THUMB_WIDTH => 250,
            MediaGallerySpecificationInterface::THUMB_HEIGHT => 250,
            MediaGallerySpecificationInterface::KEEP_ASPECT_RATIO => 1,
            MediaGallerySpecificationInterface::IMAGE_TYPES => [],
            MediaGallerySpecificationInterface::INCLUDE_MEDIA_GALLERY => 0
        ]
    ];

    public function setUp(): void
    {
        $this->feedSpecificationFactoryMock =
            $this->createMock(FeedSpecificationInterfaceFactory::class);
        $this->mediaGallerySpecificationFactoryMock =
            $this->createMock(MediaGallerySpecificationInterfaceFactory::class);
        $this->specificationBuilder = new SpecificationBuilder(
            $this->feedSpecificationFactoryMock,
            $this->mediaGallerySpecificationFactoryMock
        );
    }

    public function testBuild()
    {
        $mediaGallerySpecificationMock = $this->getMockForAbstractClass(MediaGallerySpecificationInterface::class);
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $this->feedSpecificationFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => $this->defaultValues['feed']])
            ->willReturn($feedSpecificationMock);
        $this->mediaGallerySpecificationFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => $this->defaultValues['media_gallery']])
            ->willReturn($mediaGallerySpecificationMock);
        $feedSpecificationMock->expects($this->once())
            ->method('setMediaGallerySpecification')
            ->with($mediaGallerySpecificationMock)
            ->willReturnSelf();

        $this->assertSame($feedSpecificationMock, $this->specificationBuilder->build([]));
    }
}
