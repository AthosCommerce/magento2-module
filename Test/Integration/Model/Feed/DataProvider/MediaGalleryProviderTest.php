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

namespace AthosCommerce\Feed\Test\Integration\Model\Feed\DataProvider;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\MediaGalleryProvider;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MediaGalleryProviderTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;
    /**
     * @var GetProducts
     */
    private $getProducts;
    /**
     * @var MediaGalleryProvider
     */
    private $mediaGalleryProvider;
    /**
     * @var ContextManagerInterface
     */
    private $contextManager;
    /**
     * @var Json
     */
    private $json;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->getProducts = $this->objectManager->get(GetProducts::class);
        $this->mediaGalleryProvider = $this->objectManager->get(MediaGalleryProvider::class);
        $this->contextManager = $this->objectManager->get(ContextManagerInterface::class);
        $this->json = $this->objectManager->get(Json::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products_with_images.php
     *
     * @throws \Exception
     */
    public function testGetData() : void
    {
        $imageTypes = [
            'product_small_image',
            'product_base_image',
            'invalid',
            'mini_cart_product_thumbnail'
        ];
        $specification = $this->specificationBuilder->build(['includeMediaGallery' => true, 'imageTypes' => $imageTypes]);
        $this->contextManager->setContextFromSpecification($specification);
        $products = $this->getProducts->get($specification);
        $data = $this->mediaGalleryProvider->getData($products, $specification);
        $this->assertImagesIsCorrect($data, $imageTypes);
        $this->assertImagesIsDifferent($data, $imageTypes);
        $config = [
            '/m/a/magento_image_additional' => [
                'label' => 'Additional Image Alt Text',
                'position' => 4,
            ],
            '/m/a/magento_image' => [
                'label' => 'Image Alt Text',
                'position' => 1,
            ],
            '/m/a/magento_small_image' => [
                'label' => 'Small Image Alt Text',
                'position' => 2,
            ],
            'm/a/magento_thumbnail' => [
                'label' => 'Thumbnail Image Alt Text',
                'position' => 3
            ]
        ];

        $this->assertMediaGallery($data, $config, ['/m/a/magento_image_additional_disabled']);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products_with_images_multistore.php
     *
     * @throws \Exception
     */
    public function testGetDataMultistore() : void
    {
        $imageTypes = [
            'product_small_image',
            'product_base_image',
            'invalid',
            'mini_cart_product_thumbnail'
        ];
        $specification = $this->specificationBuilder->build([
            'includeMediaGallery' => true,
            'imageTypes' => $imageTypes,
            'store' => 'fixturestore'
        ]);
        $this->contextManager->setContextFromSpecification($specification);
        $products = $this->getProducts->get($specification);
        $data = $this->mediaGalleryProvider->getData($products, $specification);
        $this->assertImagesIsCorrect($data, $imageTypes);
        $this->assertImagesIsDifferent($data, $imageTypes);
        $config = [
            '/m/a/magento_image_additional' => [
                'label' => 'Store fixturestore Additional Image Alt Text',
                'position' => 4,
            ],
            '/m/a/magento_image' => [
                'label' => 'Store fixturestore Image Alt Text',
                'position' => 1,
            ],
            '/m/a/magento_small_image' => [
                'label' => 'Store fixturestore Small Image Alt Text',
                'position' => 2,
            ],
            'm/a/magento_thumbnail' => [
                'label' => 'Store fixturestore Thumbnail Image Alt Text',
                'position' => 3
            ]
        ];

        $this->assertMediaGallery($data, $config, ['/m/a/magento_image_additional_disabled']);
    }

    /**
     * @param array $items
     * @param array $config
     * @param array $restrictedImages
     */
    private function assertMediaGallery(array $items, array $config, array $restrictedImages = []) : void
    {
        $usedImages = [];
        foreach ($items as $item) {
            $this->assertArrayHasKey('media_gallery_json', $item);
            $mediaGallery = $this->json->unserialize($item['media_gallery_json']);
            foreach ($mediaGallery as $imageData) {
                $file = $imageData['image'];
                $this->assertTrue(!in_array($file, $usedImages));
                foreach ($restrictedImages as $restrictedImage) {
                    $this->assertFalse(
                        strpos($file, $restrictedImage),
                        (string) __('%1 in restricted images array', $file)
                    );
                }

                $usedImages[] = $file;
                $imageConfig = $this->findImageConfig($file, $config);
                $label = $imageConfig['label'] ?? null;
                $position = $imageConfig['position'] ?? null;
                $disabled = $imageConfig['disabled'] ?? 0;
                $this->assertEquals($label, $imageData['label']);
                $this->assertEquals($position, $imageData['position']);
                $this->assertEquals($disabled, $imageData['disabled']);
            }
        }
    }

    /**
     * @param string $file
     * @param array $config
     * @return array
     */
    private function findImageConfig(string $file, array $config) : array
    {
        $result = [];
        foreach ($config as $fileName => $imageConfig) {
            if (strpos($file, $fileName) !== false) {
                $result = $imageConfig;
                break;
            }
        }

        return $result;
    }

    /**
     * @param array $items
     * @param array $imageTypes
     * @param array $typeMap
     */
    private function assertImagesIsCorrect(array $items, array $imageTypes, array $typeMap = []) : void
    {
        $baseTypeMap = [
            'cached_thumbnail' => 'm/a/magento_thumbnail',
            'cached_product_small_image' => 'm/a/magento_small_image',
            'cached_product_base_image' => 'm/a/magento_image',
            'cached_invalid' => 'placeholder'
        ];

        $typeMap = array_replace($baseTypeMap, $typeMap);
        $imageTypes[] = 'thumbnail';
        foreach ($items as $item) {
            foreach ($imageTypes as $imageType) {
                $key = 'cached_' . $imageType;
                $this->assertArrayHasKey($key, $item);
                $nameString = $typeMap[$key] ?? null;
                if ($nameString) {
                    $this->assertTrue(strpos($item[$key], $nameString) !== false);
                }
            }
        }
    }

    /**
     * @param array $items
     * @param array $imageTypes
     * @return void
     */
    private function assertImagesIsDifferent(array $items, array $imageTypes) : void
    {
        $existedImages = [];
        foreach ($items as $item) {
            $existedThumbnails = $existedImages['cached_thumbnail'] ?? [];
            $this->assertTrue(!in_array($item['cached_thumbnail'], $existedThumbnails));
            $existedImages['cached_thumbnail'][] = $item['cached_thumbnail'];

            foreach ($imageTypes as $imageType) {
                $key = 'cached_' . $imageType;
                $existedImagesByType = $existedImages[$key] ?? [];
                if ($imageType != 'invalid') {
                    $this->assertTrue(!in_array($item[$key], $existedImagesByType));
                    $existedImages[$key][] = $item[$key];
                } else {
                    if (!empty($existedImagesByType)) {
                        $this->assertEquals(current($existedImagesByType), $item[$key]);
                    }

                    $existedImages[$key][] = $item[$key];
                }
            }
        }
    }
}
