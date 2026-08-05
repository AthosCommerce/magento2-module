<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 */

declare(strict_types=1);

use Magento\Catalog\Api\Data\ProductExtensionFactory;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Setup\CategorySetup;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

require __DIR__ . '/configurable_attribute_first.php';

$objectManager = Bootstrap::getObjectManager();

/** @var CategorySetup $installer */
$installer = $objectManager->create(CategorySetup::class);
/** @var ProductAttributeRepositoryInterface $attributeRepository */
$attributeRepository = $objectManager->get(ProductAttributeRepositoryInterface::class);
/** @var WebsiteRepositoryInterface $websiteRepository */
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var Factory $optionsFactory */
$optionsFactory = $objectManager->get(Factory::class);
/** @var ProductExtensionFactory $extensionFactory */
$extensionFactory = $objectManager->get(ProductExtensionFactory::class);

$baseWebsite = $websiteRepository->get('base');
$attributeSetId = $installer->getAttributeSetId(Product::ENTITY, 'Default');
$attribute = $attributeRepository->get('test_configurable_first');
$options = $attribute->getOptions();
array_shift($options);

$associatedProductIds = [];
$attributeValues = [];
$productIds = [9101, 9102];
$skus = ['athos_entity_simple_1', 'athos_entity_simple_2'];

foreach ($productIds as $index => $productId) {
    $option = $options[$index];

    /** @var Product $product */
    $product = $objectManager->create(Product::class);
    $product->setTypeId(ProductType::TYPE_SIMPLE)
        ->setId($productId)
        ->setAttributeSetId($attributeSetId)
        ->setWebsiteIds([$baseWebsite->getId()])
        ->setName('Entity ID Child ' . ($index + 1))
        ->setSku($skus[$index])
        ->setPrice(10 + $index)
        ->setCustomAttribute($attribute->getAttributeCode(), $option->getValue())
        ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
        ->setStatus(Status::STATUS_ENABLED)
        ->setStockData([
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ]);

    $saved = $productRepository->save($product);
    $associatedProductIds[] = (int)$saved->getId();
    $attributeValues[] = [
        'label' => 'test',
        'attribute_id' => $attribute->getId(),
        'value_index' => $option->getValue(),
    ];
}

/** @var Product $parent */
$parent = $objectManager->create(Product::class);
$configurableAttributesData = [
    [
        'attribute_id' => $attribute->getId(),
        'code' => $attribute->getAttributeCode(),
        'label' => $attribute->getStoreLabel(),
        'position' => '0',
        'values' => $attributeValues,
    ],
];
$configurableOptions = $optionsFactory->create($configurableAttributesData);
$extensionAttributes = $parent->getExtensionAttributes() ?: $extensionFactory->create();
$extensionAttributes->setConfigurableProductOptions($configurableOptions);
$extensionAttributes->setConfigurableProductLinks($associatedProductIds);
$parent->setExtensionAttributes($extensionAttributes);

$parent->setTypeId(Configurable::TYPE_CODE)
    ->setAttributeSetId($attributeSetId)
    ->setWebsiteIds([$baseWebsite->getId()])
    ->setName('Entity ID Configurable')
    ->setSku('athos_entity_configurable')
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED)
    ->setStockData([
        'use_config_manage_stock' => 1,
        'is_in_stock' => 1,
    ]);

$productRepository->cleanCache();
$productRepository->save($parent);
