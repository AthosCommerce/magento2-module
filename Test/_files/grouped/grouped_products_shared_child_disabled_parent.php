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

use Magento\Catalog\Api\Data\ProductLinkInterface;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var ProductLinkInterfaceFactory $productLinkFactory */
$productLinkFactory = $objectManager->get(ProductLinkInterfaceFactory::class);

/** @var Product $sharedChild */
$sharedChild = $objectManager->create(Product::class);
$sharedChild->setTypeId(Type::TYPE_SIMPLE)
    ->setWebsiteIds([1])
    ->setAttributeSetId(4)
    ->setName('AthosCommerce Shared Disabled Parent Child')
    ->setSku('athoscommerce_grouped_shared_disabled_parent_child_1')
    ->setPrice(100)
    ->setVisibility(Visibility::VISIBILITY_IN_CATALOG)
    ->setStatus(Status::STATUS_ENABLED)
    ->setStockData([
        'use_config_manage_stock' => 1,
        'qty' => 100,
        'is_qty_decimal' => 0,
        'is_in_stock' => 1
    ])
    ->setData('url_key', 'athoscommerce-grouped-shared-disabled-parent-child-1');

$sharedChild = $productRepository->save($sharedChild);

$parents = [
    [
        'sku' => 'athoscommerce_grouped_shared_disabled_parent_enabled',
        'name' => 'AthosCommerce Shared Disabled Parent Enabled',
        'url_key' => 'athoscommerce-grouped-shared-disabled-parent-enabled',
        'status' => Status::STATUS_ENABLED,
    ],
    [
        'sku' => 'athoscommerce_grouped_shared_disabled_parent_disabled',
        'name' => 'AthosCommerce Shared Disabled Parent Disabled',
        'url_key' => 'athoscommerce-grouped-shared-disabled-parent-disabled',
        'status' => Status::STATUS_DISABLED,
    ],
];

foreach ($parents as $parentData) {
    /** @var Product $parent */
    $parent = $objectManager->create(Product::class);
    $parent->setTypeId(Grouped::TYPE_CODE)
        ->setWebsiteIds([1])
        ->setAttributeSetId(4)
        ->setName($parentData['name'])
        ->setSku($parentData['sku'])
        ->setVisibility(Visibility::VISIBILITY_BOTH)
        ->setStatus($parentData['status'])
        ->setStockData([
            'use_config_manage_stock' => 1,
            'is_in_stock' => 1
        ])
        ->setData('url_key', $parentData['url_key']);

    /** @var ProductLinkInterface $productLink */
    $productLink = $productLinkFactory->create();
    $productLink->setSku($parent->getSku())
        ->setLinkType('associated')
        ->setLinkedProductSku($sharedChild->getSku())
        ->setLinkedProductType($sharedChild->getTypeId())
        ->getExtensionAttributes()
        ->setQty(1);

    $parent->setProductLinks([$productLink]);
    $productRepository->save($parent);
}
