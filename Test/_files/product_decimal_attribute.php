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

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Catalog\Setup\CategorySetup;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
$installer = $objectManager->create(CategorySetup::class);
$attribute = $objectManager->create(AttributeFactory::class)->create();
$attributeRepository = $objectManager->create(ProductAttributeRepositoryInterface::class);
$entityType = $installer->getEntityTypeId(ProductAttributeInterface::ENTITY_TYPE_CODE);
if (!$attribute->loadByCode($entityType, 'decimal_attribute')->getAttributeId()) {
    $attribute->setData(
        [
            'attribute_code' => 'decimal_attribute',
            'entity_type_id' => $entityType,
            'is_global' => 1,
            'is_user_defined' => 1,
            'frontend_input' => 'price',
            'is_unique' => 0,
            'is_required' => 0,
            'is_searchable' => 0,
            'is_visible_in_advanced_search' => 0,
            'is_comparable' => 0,
            'is_filterable' => 1,
            'is_filterable_in_search' => 0,
            'is_used_for_promo_rules' => 0,
            'is_html_allowed_on_front' => 1,
            'is_visible_on_front' => 0,
            'used_in_product_listing' => 1,
            'used_for_sort_by' => 0,
            'frontend_label' => ['Decimal Attribute'],
            'backend_type' => 'decimal',
            'backend_model' => Price::class,
        ]
    );
    $attributeRepository->save($attribute);
    /* Assign attribute to attribute set */
    $installer->addAttributeToGroup(
        ProductAttributeInterface::ENTITY_TYPE_CODE,
        'Default',
        'General',
        $attribute->getId()
    );
}
