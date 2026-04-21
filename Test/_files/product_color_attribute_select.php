<?php

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

$objectManager = Bootstrap::getObjectManager();

/** @var CategorySetup $installer */
$installer = $objectManager->create(CategorySetup::class);

$attributeCode = 'athos_color';

// Prevent duplicate creation
$attribute = $installer->getAttribute(Product::ENTITY, $attributeCode);

if (!$attribute) {
    $installer->addAttribute(
        Product::ENTITY,
        $attributeCode,
        [
            'type' => 'int',
            'label' => 'athos_color',
            'input' => 'select',
            'source' => \Magento\Eav\Model\Entity\Attribute\Source\Table::class,
            'required' => false,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'user_defined' => true,
            'visible' => true,
            'used_in_product_listing' => true,
            'is_configurable' => 1,
            'option' => [
                'values' => ['Red', 'Blue']
            ]
        ]
    );

    // Assign to attribute set
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
