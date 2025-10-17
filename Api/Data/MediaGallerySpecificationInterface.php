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

namespace AthosCommerce\Feed\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface MediaGallerySpecificationInterface extends ExtensibleDataInterface
{
    const THUMB_WIDTH = 'thumb_width';
    const THUMB_HEIGHT = 'thumb_height';
    const KEEP_ASPECT_RATIO = 'keep_aspect_ratio';
    const IMAGE_TYPES = 'image_types';
    const INCLUDE_MEDIA_GALLERY = 'include_media_gallery';
    /**
     * @return int
     */
    public function getThumbWidth() : ?int;

    /**
     * @param int $width
     * @return MediaGallerySpecificationInterface
     */
    public function setThumbWidth(int $width) : self;

    /**
     * @return int
     */
    public function getThumbHeight() : ?int;

    /**
     * @param int $height
     * @return MediaGallerySpecificationInterface
     */
    public function setThumbHeight(int $height) : self;

    /**
     * @return bool
     */
    public function getKeepAspectRatio() : ?bool;

    /**
     * @param bool $flag
     * @return MediaGallerySpecificationInterface
     */
    public function setKeepAspectRatio(bool $flag) : self;

    /**
     * @return array
     */
    public function getImageTypes() : array;

    /**
     * @param array $types
     * @return MediaGallerySpecificationInterface
     */
    public function setImageTypes(array $types) : self;

    /**
     * @return bool
     */
    public function getIncludeMediaGallery() : ?bool;

    /**
     * @param bool $flag
     * @return MediaGallerySpecificationInterface
     */
    public function setIncludeMediaGallery(bool $flag) : self;


    /**
     * @return \AthosCommerce\Feed\Api\Data\MediaGallerySpecificationExtensionInterface|null
     */
    public function getExtensionAttributes(): ?\AthosCommerce\Feed\Api\Data\MediaGallerySpecificationExtensionInterface;

    /**
     * @param \AthosCommerce\Feed\Api\Data\MediaGallerySpecificationExtensionInterface $extensionAttributes
     * @return MediaGallerySpecificationInterface
     */
    public function setExtensionAttributes(
        \AthosCommerce\Feed\Api\Data\MediaGallerySpecificationExtensionInterface $extensionAttributes
    ): self;
}
