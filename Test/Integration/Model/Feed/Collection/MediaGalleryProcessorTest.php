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

namespace AthosCommerce\Feed\Test\Integration\Model\Feed\Collection;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Model\Feed\Collection\MediaGalleryProcessor;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MediaGalleryProcessorTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var MediaGalleryProcessor
     */
    private $mediaGalleryProcessor;
    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->mediaGalleryProcessor = $this->objectManager->get(MediaGalleryProcessor::class);
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_simple_with_media_gallery_entries.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products.php
     * @throws LocalizedException
     */
    public function testProcessAfterLoad() : void
    {
        $specification = $this->specificationBuilder->build(['includeMediaGallery' => 1]);
        $collection = $this->getCollection();
        $this->mediaGalleryProcessor->processAfterLoad($collection, $specification);
        $this->assertTrue($collection->getFlag('media_gallery_added'));
        foreach ($collection as $item) {
            if (in_array($item->getSku(), ['athoscommerce_simple_1', 'athoscommerce_simple_2'])) {
                // check that product without media gallery still doesn't have media gallery like from other products
                $this->assertEmpty($item->getMediaGallery('images'));
                $this->assertEmpty($item->getMediaGallery('values'));
            } else {
                $this->assertNotEmpty($item->getMediaGallery());
            }
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_simple_with_media_gallery_entries.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products.php
     */
    public function testProcessAfterLoadWithIncludeMediaGalleryIsFalse() : void
    {
        $specification = $this->specificationBuilder->build(['includeMediaGallery' => 0]);
        $collection = $this->getCollection();
        $this->mediaGalleryProcessor->processAfterLoad($collection, $specification);
        $this->assertNull($collection->getFlag('media_gallery_added'));
        foreach ($collection as $item) {
            $this->assertNull($item->getMediaGallery());
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_simple_with_media_gallery_entries.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products.php
     * @throws LocalizedException
     */
    public function testProcessAfterFetchItems() : void
    {
        $specification = $this->specificationBuilder->build(['includeMediaGallery' => 1]);
        $collection = $this->getCollection();
        $this->mediaGalleryProcessor->processAfterLoad($collection, $specification);
        $this->assertTrue($collection->getFlag('media_gallery_added'));
        foreach ($collection as $item) {
            if (in_array($item->getSku(), ['athoscommerce_simple_1', 'athoscommerce_simple_2'])) {
                // check that product without media gallery still doesn't have media gallery like from other products
                $this->assertEmpty($item->getMediaGallery('images'));
                $this->assertEmpty($item->getMediaGallery('values'));
            } else {
                $this->assertNotEmpty($item->getMediaGallery());
            }
        }

        $collection->clear();
        $this->assertTrue($collection->getFlag('media_gallery_added'));
        $this->mediaGalleryProcessor->processAfterFetchItems($collection, $specification);
        $this->assertFalse($collection->getFlag('media_gallery_added'));
    }

    /**
     * @return Collection
     */
    private function getCollection() : Collection
    {
        return $this->objectManager->create(Collection::class);
    }
}
