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

namespace AthosCommerce\Feed\Model\Feed\Collection;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\App\ResourceConnection;
use Magento\Eav\Model\Config as EavConfig;
class ExcludeProductsByVisibility implements ModifierInterface
{
    private ResourceConnection $resource;
    private EavConfig $eavConfig;
    private ConfigurableType $configurableType;

    public function __construct(
        ResourceConnection $resource,
        EavConfig $eavConfig,
        ConfigurableType $configurableType
    ) {
        $this->resource = $resource;
        $this->eavConfig = $eavConfig;
        $this->configurableType = $configurableType;
    }

    /**
     * @param Collection $collection
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return Collection
     */
    public function modify(
        Collection $collection,
        FeedSpecificationInterface $feedSpecification
    ): Collection {
        $productIntTable = $this->resource->getTableName('catalog_product_entity_int');
        $superLinkTable  = $this->resource->getTableName('catalog_product_super_link');
        $productEntityTable = $this->resource->getTableName('catalog_product_entity');

        $visibilityAttrId = (int) $this->eavConfig
            ->getAttribute('catalog_product', 'visibility')
            ->getAttributeId();

        $storeId = (int) $collection->getStoreId();

        /**
         * Join child → parent relation
         */
        $collection->getSelect()->joinLeft(
            ['super_link' => $superLinkTable],
            'super_link.product_id = e.entity_id',
            null
        );

        /**
         * Join parent entity (type_id only)
         */
        $collection->getSelect()->joinLeft(
            ['parent_entity' => $productEntityTable],
            'parent_entity.entity_id = super_link.parent_id',
            null
        );

        /**
         * Parent visibility – default store
         */
        $collection->getSelect()->joinLeft(
            ['parent_visibility_default' => $productIntTable],
            sprintf(
                'parent_visibility_default.entity_id = super_link.parent_id
         AND parent_visibility_default.attribute_id = %d
         AND parent_visibility_default.store_id = 0',
                $visibilityAttrId
            ),
            null
        );

        /**
         * Parent visibility – store override
         */
        $collection->getSelect()->joinLeft(
            ['parent_visibility_store' => $productIntTable],
            sprintf(
                'parent_visibility_store.entity_id = super_link.parent_id
         AND parent_visibility_store.attribute_id = %d
         AND parent_visibility_store.store_id = %d',
                $visibilityAttrId,
                $storeId
            ),
            null
        );

        /**
         * Exclude children of invisible configurables
         */
        $collection->getSelect()->where(
            "NOT (
        super_link.parent_id IS NOT NULL
        AND parent_entity.type_id = 'configurable'
        AND COALESCE(parent_visibility_store.value, parent_visibility_default.value) = 1
    )"
        );

        return $collection;
    }
}
