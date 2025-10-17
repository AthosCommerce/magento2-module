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

use Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\AttributesProviderInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Option\TitleToFieldNameConverter;

class FieldsProvider
{
    /**
     * @var AttributesProviderInterface
     */
    private $attributesProvider;

    private $fields = null;

    private $defaultFields = [
        // Core Magento ID Fields
        'entity_id',
        'type_id',
        'attribute_set_id',
        // AthosCommerce Generated Fields
        'cached_thumbnail',
        'stock_qty',
        'in_stock',
        'is_stock_managed',
        'categories',
        'category_hierarchy',
        'saleable',
        'url',
        'final_price',
        'regular_price',
        'max_price',
        'rating',
        'rating_count',
        'child_sku',
        'child_name'
    ];
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * FieldsProvider constructor.
     * @param AttributesProviderInterface $attributesProvider
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     * @param array $defaultFields
     */
    public function __construct(
        AttributesProviderInterface $attributesProvider,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        array $defaultFields = []
    ) {
        $this->attributesProvider = $attributesProvider;
        $this->defaultFields = array_merge($this->defaultFields, $defaultFields);
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws NoSuchEntityException
     */
    public function getFields(FeedSpecificationInterface $feedSpecification) : array
    {
        if (is_array($this->fields)) {
            return $this->fields;
        }

        $fields = $this->defaultFields;
        if($feedSpecification->getIncludeMenuCategories()) {
            $fields[] = 'menu_hierarchy';
        }

        if($feedSpecification->getIncludeUrlHierarchy()) {
            $fields[] = 'url_hierarchy';
        }

        if($feedSpecification->getIncludeChildPrices()) {
            $fields[] = 'child_final_price';
        }

        if($feedSpecification->getIncludeJSONConfig()) {
            $fields[] = 'json_config';
            $fields[] = 'swatch_json_config';
        }

        if($feedSpecification->getIncludeTierPricing()) {
            $fields[] = 'tier_pricing';
        }

        if($feedSpecification->getMediaGallerySpecification()->getIncludeMediaGallery()) {
            $fields[] = 'media_gallery_json';
        }

        foreach($feedSpecification->getMediaGallerySpecification()->getImageTypes() as $type) {
            $fields[] = 'cached_'.$type;
        }

        $storeId = (int) $this->storeManager->getStore($feedSpecification->getStoreCode())->getId();
        $fields = array_merge($fields, $this->attributesProvider->getAttributeCodes($feedSpecification));
        $options = $this->collectionFactory->create()
            ->addTitleToResult($storeId);

        foreach($options as $option) {
            $fields[] = TitleToFieldNameConverter::convert($option->getTitle());
        }

        // Remove ignored fields
        $this->fields = array_unique(array_diff($fields, $feedSpecification->getIgnoreFields()));
        return $this->fields;
    }

    /**
     *
     */
    public function reset() : void
    {
        $this->fields = null;
    }
}
