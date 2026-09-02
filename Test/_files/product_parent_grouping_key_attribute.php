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

declare(strict_types=1);

use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var CategorySetup $installer */
$installer = $objectManager->create(CategorySetup::class);

$attributeCode = 'test_parent_group_code';
$attribute = $installer->getAttribute(Product::ENTITY, $attributeCode);

if (!$attribute) {
    $installer->addAttribute(
        Product::ENTITY,
        $attributeCode,
        [
            'type' => 'varchar',
            'label' => 'Test Parent Group Code',
            'input' => 'text',
            'required' => false,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'user_defined' => true,
            'visible' => true,
            'used_in_product_listing' => true,
        ]
    );

    $attributeSetId = $installer->getAttributeSetId(Product::ENTITY, 'Default');
    $groupId = $installer->getDefaultAttributeGroupId(Product::ENTITY, $attributeSetId);
    $attributeId = $installer->getAttributeId(Product::ENTITY, $attributeCode);

    $installer->addAttributeToGroup(
        Product::ENTITY,
        $attributeSetId,
        $groupId,
        $attributeId
    );
}
