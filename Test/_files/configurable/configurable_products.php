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

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

require __DIR__ . '/configurable_attribute_first.php';
require __DIR__ . '/configurable_attribute_second.php';

$objectManager = Bootstrap::getObjectManager();

/** @var WebsiteRepositoryInterface $websiteRepository */
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
$baseWebsite = $websiteRepository->get('base');

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var ProductInterfaceFactory $productInterfaceFactory */
$productInterfaceFactory = $objectManager->get(ProductInterfaceFactory::class);

/** @var ProductAttributeRepositoryInterface $attributeRepository */
$attributeRepository = $objectManager->get(ProductAttributeRepositoryInterface::class);
/** @var $firstAttribute Attribute */
$firstAttribute = $attributeRepository->get('test_configurable_first');
/** @var $secondAttribute Attribute */
$secondAttribute = $attributeRepository->get('test_configurable_second');
/** @var AttributeOptionInterface[] $firstAttributeOptions */
$firstAttributeOptions = $firstAttribute->getOptions();
/** @var AttributeOptionInterface[] $secondAttributeOptions */
$secondAttributeOptions = $secondAttribute->getOptions();

/** @var $installer EavSetup */
$installer = $objectManager->get(EavSetup::class);
$attributeSetId = $installer->getAttributeSetId(Product::ENTITY, 'Default');

/** @var Factory $optionsFactory */
$optionsFactory = $objectManager->get(Factory::class);
/* Create simple products per each option value*/

$attributeValues = [];
$associatedProductIds = [];
$productIds = [10, 20, 30, 40];
array_shift($firstAttributeOptions); //remove the first option which is empty

foreach ($firstAttributeOptions as $option) {
    /** @var $product Product */
    $product = $productInterfaceFactory->create();
    $productId = array_shift($productIds);
    $product->setTypeId(Type::TYPE_SIMPLE)
        ->setAttributeSetId($attributeSetId)
        ->setWebsiteIds([$baseWebsite->getId()])
        ->setName('AthosCommerce Test Configurable Option' . $option->getLabel())
        ->setSku('athoscommerce_configurable_test_simple_' . $productId)
        ->setPrice($productId)
        ->setTestConfigurableFirst($option->getValue())
        ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
        ->setStatus(Status::STATUS_ENABLED)
        ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
        ->setData('boolean_attribute', true)
        ->setData('decimal_attribute', $productId);
    $simple1 = $productRepository->save($product);

    $attributeValues[] = [
        'label' => 'test',
        'attribute_id' => $firstAttribute->getId(),
        'value_index' => $option->getValue(),
    ];
    $associatedProductIds[] = $simple1->getId();
}

/** @var $product Product */
$product = $productInterfaceFactory->create();
$configurableAttributesData = [
    [
        'attribute_id' => $firstAttribute->getId(),
        'code' => $firstAttribute->getAttributeCode(),
        'label' => $firstAttribute->getStoreLabel(),
        'position' => '0',
        'values' => $attributeValues,
    ],
];
$configurableOptions = $optionsFactory->create($configurableAttributesData);
$extensionConfigurableAttributes = $product->getExtensionAttributes();
$extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
$extensionConfigurableAttributes->setConfigurableProductLinks($associatedProductIds);
$product->setExtensionAttributes($extensionConfigurableAttributes);

$product->setTypeId(Configurable::TYPE_CODE)
    ->setAttributeSetId($attributeSetId)
    ->setWebsiteIds([$baseWebsite->getId()])
    ->setName('AthosCommerce Configurable Product')
    ->setSku('athoscommerce_configurable_test_configurable')
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1]);
$productRepository->cleanCache();
$productRepository->save($product);

/* Create simple products per each option value*/
/** @var AttributeOptionInterface[] $options */
$firstAttributeOptions = $firstAttribute->getOptions();

$attributeValues = [];
$associatedProductIds = [];
$productIds = [50, 60];
array_shift($firstAttributeOptions); //remove the first option which is empty

$firstAttributeValues = [];
$secondAttributeValues = [];
$i = 1;
foreach ($productIds as $productId) {
    $firstOption = $firstAttributeOptions[$i];
    $secondOption = $secondAttributeOptions[$i];
    /** @var $product Product */
    $product = $productInterfaceFactory->create();
    $product->setTypeId(Type::TYPE_SIMPLE)
        ->setId($productId)
        ->setAttributeSetId($attributeSetId)
        ->setWebsiteIds([1])
        ->setName('AthosCommerce Test 2 Attributes Configurable Option ' . $firstOption->getLabel() . '-' . $secondOption->getLabel())
        ->setSku('athoscommerce_configurable_test_simple_' . $productId)
        ->setPrice($productId)
        ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
        ->setStatus(Status::STATUS_ENABLED)
        ->setData('boolean_attribute', true)
        ->setData('decimal_attribute', $productId)
        ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1]);
    $customAttributes = [
        $firstAttribute->getAttributeCode() => $firstOption->getValue(),
        $secondAttribute->getAttributeCode() => $secondOption->getValue()
    ];
    foreach ($customAttributes as $attributeCode => $attributeValue) {
        $product->setCustomAttributes($customAttributes);
    }
    $product = $productRepository->save($product);

    $firstAttributeValues[] = [
        'label' => 'test first ' . $i,
        'attribute_id' => $firstAttribute->getId(),
        'value_index' => $firstOption->getValue(),
    ];
    $secondAttributeValues[] = [
        'label' => 'test second ' . $i,
        'attribute_id' => $secondAttribute->getId(),
        'value_index' => $secondOption->getValue(),
    ];
    $associatedProductIds[] = $product->getId();
    $i++;
}

/** @var $product Product */
$product = $productInterfaceFactory->create();
$configurableAttributesData = [
    [
        'attribute_id' => $firstAttribute->getId(),
        'code' => $firstAttribute->getAttributeCode(),
        'label' => $firstAttribute->getStoreLabel(),
        'position' => '0',
        'values' => $firstAttributeValues,
    ],
    [
        'attribute_id' => $secondAttribute->getId(),
        'code' => $secondAttribute->getAttributeCode(),
        'label' => $secondAttribute->getStoreLabel(),
        'position' => '1',
        'values' => $secondAttributeValues,
    ],
];
$configurableOptions = $optionsFactory->create($configurableAttributesData);
$extensionConfigurableAttributes = $product->getExtensionAttributes();
$extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
$extensionConfigurableAttributes->setConfigurableProductLinks($associatedProductIds);
$product->setExtensionAttributes($extensionConfigurableAttributes);

$product->setTypeId(Configurable::TYPE_CODE)
    ->setAttributeSetId($attributeSetId)
    ->setWebsiteIds([$baseWebsite->getId()])
    ->setName('AthosCommerce Configurable Product 12345')
    ->setSku('athoscommerce_configurable_test_configurable_2_attributes')
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1]);
$productRepository->cleanCache();
$productRepository->save($product);
