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

namespace AthosCommerce\Feed\Api\Data;

if (!interface_exists(MediaGallerySpecificationExtensionInterface::class, false)) {
    interface MediaGallerySpecificationExtensionInterface extends \Magento\Framework\Api\ExtensionAttributesInterface
    {
    }
}

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\Specification;

use AthosCommerce\Feed\Api\Data\MediaGallerySpecificationExtensionInterface;
use AthosCommerce\Feed\Api\Data\MediaGallerySpecificationInterface;
use AthosCommerce\Feed\Model\Feed\Specification\MediaGallery;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;

class MediaGalleryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ExtensionAttributesFactory
     */
    private $extensionFactoryMock;

    /**
     * @var AttributeValueFactory
     */
    private $attributeValueFactoryMock;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->extensionFactoryMock = $this->createMock(ExtensionAttributesFactory::class);
        $this->attributeValueFactoryMock = $this->createMock(AttributeValueFactory::class);
    }

    /**
     * @param array $data
     * @return MediaGallery
     */
    private function createSpecification(array $data = array())
    {
        return new MediaGallery(
            $this->extensionFactoryMock,
            $this->attributeValueFactoryMock,
            $data
        );
    }

    /**
     * @return void
     */
    public function testGetThumbWidthReturnsNullByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertNull($specification->getThumbWidth());
    }

    /**
     * @return void
     */
    public function testSetThumbWidthAndGetThumbWidth()
    {
        $specification = $this->createSpecification();

        $result = $specification->setThumbWidth(250);

        $this->assertInstanceOf(MediaGallerySpecificationInterface::class, $result);
        $this->assertSame(250, $specification->getThumbWidth());
    }

    /**
     * @return void
     */
    public function testGetThumbWidthCastsRawDataToInt()
    {
        $specification = $this->createSpecification(array(
            MediaGallerySpecificationInterface::THUMB_WIDTH => '250'
        ));

        $this->assertSame(250, $specification->getThumbWidth());
    }

    /**
     * @return void
     */
    public function testGetThumbHeightReturnsNullByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertNull($specification->getThumbHeight());
    }

    /**
     * @return void
     */
    public function testSetThumbHeightAndGetThumbHeight()
    {
        $specification = $this->createSpecification();

        $result = $specification->setThumbHeight(300);

        $this->assertInstanceOf(MediaGallerySpecificationInterface::class, $result);
        $this->assertSame(300, $specification->getThumbHeight());
    }

    /**
     * @return void
     */
    public function testGetThumbHeightCastsRawDataToInt()
    {
        $specification = $this->createSpecification(array(
            MediaGallerySpecificationInterface::THUMB_HEIGHT => '300'
        ));

        $this->assertSame(300, $specification->getThumbHeight());
    }

    /**
     * @return void
     */
    public function testGetKeepAspectRatioReturnsNullByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertNull($specification->getKeepAspectRatio());
    }

    /**
     * @return void
     */
    public function testSetKeepAspectRatioTrueAndGetKeepAspectRatio()
    {
        $specification = $this->createSpecification();

        $result = $specification->setKeepAspectRatio(true);

        $this->assertInstanceOf(MediaGallerySpecificationInterface::class, $result);
        $this->assertTrue($specification->getKeepAspectRatio());
    }

    /**
     * @return void
     */
    public function testSetKeepAspectRatioFalseAndGetKeepAspectRatio()
    {
        $specification = $this->createSpecification();

        $result = $specification->setKeepAspectRatio(false);

        $this->assertInstanceOf(MediaGallerySpecificationInterface::class, $result);
        $this->assertFalse($specification->getKeepAspectRatio());
    }

    /**
     * @return void
     */
    public function testGetKeepAspectRatioCastsRawDataToBool()
    {
        $trueSpecification = $this->createSpecification(array(
            MediaGallerySpecificationInterface::KEEP_ASPECT_RATIO => 1
        ));
        $falseSpecification = $this->createSpecification(array(
            MediaGallerySpecificationInterface::KEEP_ASPECT_RATIO => 0
        ));

        $this->assertTrue($trueSpecification->getKeepAspectRatio());
        $this->assertFalse($falseSpecification->getKeepAspectRatio());
    }

    /**
     * @return void
     */
    public function testGetImageTypesReturnsEmptyArrayByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertSame(array(), $specification->getImageTypes());
    }

    /**
     * @return void
     */
    public function testSetImageTypesAndGetImageTypes()
    {
        $types = array('image', 'small_image', 'thumbnail');
        $specification = $this->createSpecification();

        $result = $specification->setImageTypes($types);

        $this->assertInstanceOf(MediaGallerySpecificationInterface::class, $result);
        $this->assertSame($types, $specification->getImageTypes());
    }

    /**
     * @return void
     */
    public function testGetIncludeMediaGalleryReturnsNullByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertNull($specification->getIncludeMediaGallery());
    }

    /**
     * @return void
     */
    public function testSetIncludeMediaGalleryTrueAndGetIncludeMediaGallery()
    {
        $specification = $this->createSpecification();

        $result = $specification->setIncludeMediaGallery(true);

        $this->assertInstanceOf(MediaGallerySpecificationInterface::class, $result);
        $this->assertTrue($specification->getIncludeMediaGallery());
    }

    /**
     * @return void
     */
    public function testSetIncludeMediaGalleryFalseAndGetIncludeMediaGallery()
    {
        $specification = $this->createSpecification();

        $result = $specification->setIncludeMediaGallery(false);

        $this->assertInstanceOf(MediaGallerySpecificationInterface::class, $result);
        $this->assertFalse($specification->getIncludeMediaGallery());
    }

    /**
     * @return void
     */
    public function testGetIncludeMediaGalleryCastsRawDataToBool()
    {
        $trueSpecification = $this->createSpecification(array(
            MediaGallerySpecificationInterface::INCLUDE_MEDIA_GALLERY => 1
        ));
        $falseSpecification = $this->createSpecification(array(
            MediaGallerySpecificationInterface::INCLUDE_MEDIA_GALLERY => 0
        ));

        $this->assertTrue($trueSpecification->getIncludeMediaGallery());
        $this->assertFalse($falseSpecification->getIncludeMediaGallery());
    }

    /**
     * @return void
     */
    public function testSetExtensionAttributesAndGetExtensionAttributes()
    {
        $extensionAttributesMock = $this->createMock(MediaGallerySpecificationExtensionInterface::class);
        $specification = $this->createSpecification();

        $result = $specification->setExtensionAttributes($extensionAttributesMock);

        $this->assertInstanceOf(MediaGallerySpecificationInterface::class, $result);
        $this->assertSame($extensionAttributesMock, $specification->getExtensionAttributes());
    }
}
