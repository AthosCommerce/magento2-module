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
use Magento\Catalog\Api\Data\ProductAttributeInterfaceFactory;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var ProductAttributeRepositoryInterface $attributeRepository */
$attributeRepository = $objectManager->get(ProductAttributeRepositoryInterface::class);
/** @var ProductAttributeInterfaceFactory $attributeFactory */
$attributeFactory = $objectManager->get(ProductAttributeInterfaceFactory::class);

/** @var ProductInterfaceFactory $productInterfaceFactory */
$productInterfaceFactory = $objectManager->get(ProductInterfaceFactory::class);

/** @var $installer CategorySetup */
$installer = $objectManager->get(CategorySetup::class);
$attributeSetId = $installer->getAttributeSetId(Product::ENTITY, 'Default');
$groupId = $installer->getDefaultAttributeGroupId(Product::ENTITY, $attributeSetId);

/** @var ProductAttributeInterface $attributeModel */
$attributeModel = $attributeFactory->create();
$attributeModel->setData([
    'attribute_code' => 'athos_material_multi',
    'entity_type_id' => CategorySetup::CATALOG_PRODUCT_ENTITY_TYPE_ID,
    'is_global' => 0,
    'is_user_defined' => 1,
    'frontend_input' => 'multiselect',
    'backend_type' => 'varchar',
    'backend_model' => \Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend::class,
    'source_model' => \Magento\Eav\Model\Entity\Attribute\Source\Table::class,
    'is_required' => 0,
    'is_searchable' => 1,
    'is_filterable' => 1,
    'is_filterable_in_search' => 1,
    'is_visible_on_front' => 1,
    'used_in_product_listing' => 1,

    'frontend_label' => ['Material'],
    'option' => [
        'value' => [
            'cotton'     => ['Cotton'],
            'wool'       => ['Wool'],
            'leather'    => ['Leather'],
            'silk'       => ['Silk'],
            'denim'      => ['Denim'],
            'linen'      => ['Linen'],
            'polyester'  => ['Polyester'],
            'nylon'      => ['Nylon'],
            'rayon'      => ['Rayon'],
            'spandex'    => ['Spandex']
        ]
    ],
]);

$attribute = $attributeRepository->save($attributeModel);
$installer->addAttributeToGroup(Product::ENTITY, $attributeSetId, $groupId, $attribute->getId());
