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

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Catalog\Model\Product;

$objectManager = Bootstrap::getObjectManager();

$productFactory = $objectManager->get(ProductFactory::class);
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$storeManager = $objectManager->get(StoreManagerInterface::class);
$attributeRepository = $objectManager->get(AttributeRepositoryInterface::class);


$resolveOptionIdsByLabels = function (string $attributeCode, array $labels) use ($attributeRepository) {
    $attribute = $attributeRepository->get(Product::ENTITY, $attributeCode);

    $options = $attribute->getSource()->getAllOptions(false);

    $map = [];
    foreach ($options as $option) {
        if (empty($option['value'])) {
            continue;
        }
        $map[strtolower($option['label'])] = (int)$option['value'];
    }

    $resolved = [];
    foreach ($labels as $label) {
        $key = strtolower($label);
        if (!isset($map[$key])) {
            throw new \Exception(
                sprintf(
                    'Option "%s" not found for attribute "%s"',
                    $label,
                    $attributeCode
                )
            );
        }
        $resolved[] = $map[$key];
    }

    return $resolved;
};

$resolveOptionIdByLabel = function (string $attributeCode, string $label) use ($resolveOptionIdsByLabels) {
    return $resolveOptionIdsByLabels($attributeCode, [$label])[0];
};

$product = $productFactory->create();

$product->setTypeId('simple')
    ->setAttributeSetId(4)
    ->setName('Simple 1 with Attributes')
    ->setSku('athoscommerce_simple_attributes_1')
    ->setPrice(10.45)
    ->setTaxClassId(0)
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED)
    ->setStockData([
        'qty' => 100,
        'is_in_stock' => 1,
        'manage_stock' => 1,
        'use_config_manage_stock' => 0
    ])
    ->setWebsiteIds([$storeManager->getStore()->getWebsiteId()])
    ->setCategoryIds([2])
    ->setData('boolean_attribute', true)
    ->setData('decimal_attribute', 50);

$product->setData(
    'athos_material_multi',
    $resolveOptionIdsByLabels(
        'athos_material_multi',
        ['Polyester', 'Cotton', 'Linen']
    )
);

$product->setData(
    'athos_color_multi',
    $resolveOptionIdsByLabels(
        'athos_color_multi',
        ['Red', 'Black', 'White', 'Blue', 'Green']
    )
);

$product->setData(
    'athos_size_multi',
    $resolveOptionIdsByLabels(
        'athos_size_multi',
        ['S', 'M', 'L', '5XL', '3XL']
    )
);

$product->setData(
    'athos_brand_select_attribute',
    $resolveOptionIdByLabel(
        'athos_brand_select_attribute',
        'Adidas'
    )
);

$productRepository->save($product);
