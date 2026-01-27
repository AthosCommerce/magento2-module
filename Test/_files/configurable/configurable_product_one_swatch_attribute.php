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

use Magento\Catalog\Api\Data\ProductExtensionFactory;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Setup\CategorySetup;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

require __DIR__ . '/../product_flavour_swatch_attribute.php';

/** @var ObjectManagerInterface $objectManager */
$objectManager = Bootstrap::getObjectManager();
$installer = $objectManager->create(CategorySetup::class);
/** @var ProductAttributeRepositoryInterface $productAttributeRepository */
$productAttributeRepository = $objectManager->get(ProductAttributeRepositoryInterface::class);

$secondAttribute = $productAttributeRepository->get('flavour_visual_swatch_attribute');

$secondAttributeOptions = $secondAttribute->getOptions();
/** @var WebsiteRepositoryInterface $websiteRepository */
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
$baseWebsite = $websiteRepository->get('base');
/** @var ProductAttributeRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);

$secondAttributeValues = [];
$associatedProductIds = [];
$associatedProductIdsViaSecondAttribute = [];
$attributeSetId = $installer->getAttributeSetId(Product::ENTITY, 'Default');
$productFactory = $objectManager->get(ProductFactory::class);
$rootCategoryId = $baseWebsite->getDefaultStore()->getRootCategoryId();

array_shift($secondAttributeOptions);

foreach ($secondAttributeOptions as $secondAttrOption) {
    $secondAttributeValues[] = [
        'label' => 'test2',
        'attribute_id' => $secondAttribute->getId(),
        'value_index' => $secondAttrOption->getValue(),
    ];
}

$allAttributes = [$secondAttribute];
$optionsFactory = $objectManager->get(Factory::class);

foreach ($allAttributes as $attribute) {
    $configurableAttributesData[] =
        [
            'attribute_id' => $attribute->getId(),
            'code' => $attribute->getAttributeCode(),
            'label' => $attribute->getStoreLabel(),
            'position' => '0',
            'values' => $secondAttributeValues,
        ];
}

$configurableOptions = $optionsFactory->create($configurableAttributesData);
$product = $productFactory->create();
/** @var ProductExtensionFactory $extensionAttributesFactory */
$extensionAttributesFactory = $objectManager->get(ProductExtensionFactory::class);
$extensionConfigurableAttributes = $product->getExtensionAttributes() ?: $extensionAttributesFactory->create();
$extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
$extensionConfigurableAttributes->setConfigurableProductLinks($associatedProductIds);
$product->setExtensionAttributes($extensionConfigurableAttributes);

$product->setTypeId(Configurable::TYPE_CODE)
    ->setAttributeSetId($product->getDefaultAttributeSetId())
    ->setWebsiteIds([$baseWebsite->getId()])
    ->setName('Configurable Product')
    ->setSku('configurable')
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED)
    ->setCategoryIds([$rootCategoryId])
    ->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1]);
$productRepository->save($product);
