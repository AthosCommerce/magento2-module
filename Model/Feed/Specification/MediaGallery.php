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

namespace AthosCommerce\Feed\Model\Feed\Specification;

use Magento\Framework\Api\AbstractExtensibleObject;
use AthosCommerce\Feed\Api\Data\MediaGallerySpecificationInterface;
use AthosCommerce\Feed\Api\Data\MediaGallerySpecificationExtensionInterface;

class MediaGallery extends AbstractExtensibleObject implements MediaGallerySpecificationInterface
{

    /**
     * @return int
     */
    public function getThumbWidth(): ?int
    {
        return !is_null($this->_get(self::THUMB_WIDTH))
            ? (int) $this->_get(self::THUMB_WIDTH)
            : null;
    }

    /**
     * @param int $width
     * @return MediaGallerySpecificationInterface
     */
    public function setThumbWidth(int $width): MediaGallerySpecificationInterface
    {
        return $this->setData(self::THUMB_WIDTH, $width);
    }

    /**
     * @return int
     */
    public function getThumbHeight(): ?int
    {
        return !is_null($this->_get(self::THUMB_HEIGHT))
            ? (int) $this->_get(self::THUMB_HEIGHT)
            : null;
    }

    /**
     * @param int $height
     * @return MediaGallerySpecificationInterface
     */
    public function setThumbHeight(int $height): MediaGallerySpecificationInterface
    {
        return $this->setData(self::THUMB_HEIGHT, $height);
    }

    /**
     * @return bool
     */
    public function getKeepAspectRatio(): ?bool
    {
        return !is_null($this->_get(self::KEEP_ASPECT_RATIO))
            ? (bool) $this->_get(self::KEEP_ASPECT_RATIO)
            : null;
    }

    /**
     * @param bool $flag
     * @return MediaGallerySpecificationInterface
     */
    public function setKeepAspectRatio(bool $flag): MediaGallerySpecificationInterface
    {
        return $this->setData(self::KEEP_ASPECT_RATIO, $flag);
    }

    /**
     * @return array
     */
    public function getImageTypes(): array
    {
        return $this->_get(self::IMAGE_TYPES) ?? [];
    }

    /**
     * @param array $types
     * @return MediaGallerySpecificationInterface
     */
    public function setImageTypes(array $types): MediaGallerySpecificationInterface
    {
        return $this->setData(self::IMAGE_TYPES, $types);
    }

    /**
     * @return bool
     */
    public function getIncludeMediaGallery(): ?bool
    {
        return !is_null($this->_get(self::INCLUDE_MEDIA_GALLERY))
            ? (bool) $this->_get(self::INCLUDE_MEDIA_GALLERY)
            : null;
    }

    /**
     * @param bool $flag
     * @return MediaGallerySpecificationInterface
     */
    public function setIncludeMediaGallery(bool $flag): MediaGallerySpecificationInterface
    {
        return $this->setData(self::INCLUDE_MEDIA_GALLERY, $flag);
    }

    /**
     * @return MediaGallerySpecificationExtensionInterface|null
     */
    public function getExtensionAttributes(): ?MediaGallerySpecificationExtensionInterface
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @param MediaGallerySpecificationExtensionInterface $extensionAttributes
     * @return MediaGallerySpecificationInterface
     */
    public function setExtensionAttributes(MediaGallerySpecificationExtensionInterface $extensionAttributes): MediaGallerySpecificationInterface
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
