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

namespace AthosCommerce\Feed\Model\Feed;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterfaceFactory;
use AthosCommerce\Feed\Api\Data\MediaGallerySpecificationInterface;
use AthosCommerce\Feed\Api\Data\MediaGallerySpecificationInterfaceFactory;
use AthosCommerce\Feed\Api\MetadataInterface;

class SpecificationBuilder implements SpecificationBuilderInterface
{
    /**
     * @var FeedSpecificationInterfaceFactory
     */
    private $feedSpecificationFactory;
    /**
     * @var MediaGallerySpecificationInterfaceFactory
     */
    private $mediaGallerySpecificationFactory;
    /**
     * @var array
     */
    private $keyMap = [
        'store' => FeedSpecificationInterface::STORE_CODE,
        'hierarchySeparator' => FeedSpecificationInterface::HIERARCHY_SEPARATOR,
        'multiValuedSeparator' => FeedSpecificationInterface::MULTI_VALUED_SEPARATOR,
        'includeUrlHierarchy' => FeedSpecificationInterface::INCLUDE_URL_HIERARCHY,
        'includeMenuCategories' => FeedSpecificationInterface::INCLUDE_MENU_CATEGORIES,
        'includeJSONConfig' => FeedSpecificationInterface::INCLUDE_JSON_CONFIG,
        'includeChildPrices' => FeedSpecificationInterface::INCLUDE_CHILD_PRICES,
        'includeTierPricing' => FeedSpecificationInterface::INCLUDE_TIER_PRICES,
        'customerId' => FeedSpecificationInterface::CUSTOMER_ID,
        'childFields' => FeedSpecificationInterface::CHILD_FIELDS,
        'includeOutOfStock' => FeedSpecificationInterface::INCLUDE_OUT_OF_STOCK,
        'ignoreFields' => FeedSpecificationInterface::IGNORE_FIELDS,
        'format' => FeedSpecificationInterface::FORMAT,
        'thumbWidth' => MediaGallerySpecificationInterface::THUMB_WIDTH,
        'thumbHeight' => MediaGallerySpecificationInterface::THUMB_HEIGHT,
        'keepAspectRatio' => MediaGallerySpecificationInterface::KEEP_ASPECT_RATIO,
        'imageTypes' => MediaGallerySpecificationInterface::IMAGE_TYPES,
        'includeMediaGallery' => MediaGallerySpecificationInterface::INCLUDE_MEDIA_GALLERY,
        'preSignedUrl' => FeedSpecificationInterface::PRE_SIGNED_URL,
        'isMsiEnabled' => FeedSpecificationInterface::MSI_STATUS,
        'swatchOptionSourceFieldNames' => FeedSpecificationInterface::SETTING_NAME_SWATCH_OPTION_FIELD_NAMES,
        'allVariantsAdditionalFields'  => FeedSpecificationInterface::SETTING_NAME_VARIANT_ADDITIONAL_FIELDS,
        'excludedProductIds' => FeedSpecificationInterface::EXCLUDE_PRODUCT_IDS
    ];
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
            FeedSpecificationInterface::MSI_STATUS => false,
            FeedSpecificationInterface::SETTING_NAME_SWATCH_OPTION_FIELD_NAMES => ['color'],
            FeedSpecificationInterface::SETTING_NAME_VARIANT_ADDITIONAL_FIELDS => [],
            FeedSpecificationInterface::EXCLUDE_PRODUCT_IDS => [],
        ],
        'media_gallery' => [
            MediaGallerySpecificationInterface::THUMB_WIDTH => 250,
            MediaGallerySpecificationInterface::THUMB_HEIGHT => 250,
            MediaGallerySpecificationInterface::KEEP_ASPECT_RATIO => 1,
            MediaGallerySpecificationInterface::IMAGE_TYPES => [],
            MediaGallerySpecificationInterface::INCLUDE_MEDIA_GALLERY => 0
        ]
    ];

    /**
     * SpecificationBuilder constructor.
     * @param FeedSpecificationInterfaceFactory $feedSpecificationFactory
     * @param MediaGallerySpecificationInterfaceFactory $mediaGallerySpecificationFactory
     * @param array $keyMap
     * @param array $defaultValues
     */
    public function __construct(
        FeedSpecificationInterfaceFactory $feedSpecificationFactory,
        MediaGallerySpecificationInterfaceFactory $mediaGallerySpecificationFactory,
        array $keyMap = [],
        array $defaultValues = []
    ) {
        $this->feedSpecificationFactory = $feedSpecificationFactory;
        $this->mediaGallerySpecificationFactory = $mediaGallerySpecificationFactory;
        $this->keyMap = array_merge_recursive($this->keyMap, $keyMap);
        $this->defaultValues = array_merge_recursive($this->defaultValues, $defaultValues);
    }

    /**
     * @param array $data
     * @return FeedSpecificationInterface
     */
    public function build(array $data): FeedSpecificationInterface
    {
        $data = $this->convertKeys($data);
        $mediaGallery = $this->buildMediaGallery($data);
        $data = $this->addDefaultValues($data, $this->defaultValues['feed']);
        /** @var FeedSpecificationInterface $specification */
        $specification = $this->feedSpecificationFactory->create(['data' => $data]);
        $specification->setMediaGallerySpecification($mediaGallery);

        return $specification;
    }

    /**
     * @param array $data
     * @return MediaGallerySpecificationInterface
     */
    private function buildMediaGallery(array $data) : MediaGallerySpecificationInterface
    {
        $defaultValues = $this->defaultValues['media_gallery'];
        $data = $this->addDefaultValues($data, $defaultValues);
        return $this->mediaGallerySpecificationFactory->create(['data' => $data]);
    }

    /**
     * @param array $data
     * @param array $defaultValues
     * @return array
     */
    private function addDefaultValues(array $data, array $defaultValues) : array
    {
        foreach ($defaultValues as $key => $value) {
            if (!array_key_exists($key, $data)) {
                $data[$key] = $value;
            }
        }

        return $data;
    }
    /**
     * @param array $data
     * @return array
     */
    private function convertKeys(array $data) : array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $newKey = $this->keyMap[$key] ?? $key;
            $result[$newKey] = $value;
        }

        return $result;
    }
}
