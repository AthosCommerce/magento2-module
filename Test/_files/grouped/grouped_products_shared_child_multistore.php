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

use Magento\Catalog\Api\Data\ProductLinkInterface;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var ProductLinkInterfaceFactory $productLinkFactory */
$productLinkFactory = $objectManager->get(ProductLinkInterfaceFactory::class);

/**
 * Shared child product
 */
/** @var Product $sharedChild */
$sharedChild = $objectManager->create(Product::class);
$sharedChild->setTypeId(Type::TYPE_SIMPLE)
    ->setWebsiteIds([1])
    ->setAttributeSetId(4)
    ->setName('AthosCommerce Shared Child Default')
    ->setSku('athoscommerce_grouped_shared_child_1')
    ->setPrice(100)
    ->setVisibility(Visibility::VISIBILITY_IN_CATALOG)
    ->setStatus(Status::STATUS_ENABLED)
    ->setStockData([
        'use_config_manage_stock' => 1,
        'qty' => 100,
        'is_qty_decimal' => 0,
        'is_in_stock' => 1
    ])
    ->setData('url_key', 'athoscommerce-grouped-shared-child-1');

$sharedChild = $productRepository->save($sharedChild);

/**
 * Parent definitions
 */
$parents = [
    [
        'sku' => 'athoscommerce_grouped_shared_parent_1',
        'name' => 'AthosCommerce Shared Parent 1 Default',
        'store_name' => 'AthosCommerce Shared Parent 1 Fixture Store',
        'url_key' => 'athoscommerce-grouped-shared-parent-1',
        'store_url_key' => 'fixturestore-athoscommerce-grouped-shared-parent-1',
    ],
    [
        'sku' => 'athoscommerce_grouped_shared_parent_2',
        'name' => 'AthosCommerce Shared Parent 2 Default',
        'store_name' => 'AthosCommerce Shared Parent 2 Fixture Store',
        'url_key' => 'athoscommerce-grouped-shared-parent-2',
        'store_url_key' => 'fixturestore-athoscommerce-grouped-shared-parent-2',
    ],
];

$createdParents = [];

foreach ($parents as $parentData) {
    /** @var Product $parent */
    $parent = $objectManager->create(Product::class);
    $parent->setTypeId(Grouped::TYPE_CODE)
        ->setWebsiteIds([1])
        ->setAttributeSetId(4)
        ->setName($parentData['name'])
        ->setSku($parentData['sku'])
        ->setVisibility(Visibility::VISIBILITY_BOTH)
        ->setStatus(Status::STATUS_ENABLED)
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

    $createdParents[] = $productRepository->save($parent);
}

/**
 * Optional multistore overrides on fixturestore store view.
 * If fixturestore does not exist in the environment, this block safely does nothing.
 */
try {
    /** @var Store $fixtureStore */
    $fixtureStore = $objectManager->get(StoreManagerInterface::class)->getStore('fixturestore');
    $fixtureStoreId = (int)$fixtureStore->getId();

    foreach ($createdParents as $index => $parent) {
        $parentData = $parents[$index];

        /** @var Product $storeParent */
        $storeParent = $productRepository->get($parent->getSku(), false, $fixtureStoreId, true);
        $storeParent->setStoreId($fixtureStoreId);
        $storeParent->setName($parentData['store_name']);
        $storeParent->setData('url_key', $parentData['store_url_key']);
        $productRepository->save($storeParent);
    }

    /** @var Product $storeChild */
    $storeChild = $productRepository->get($sharedChild->getSku(), false, $fixtureStoreId, true);
    $storeChild->setStoreId($fixtureStoreId);
    $storeChild->setName('AthosCommerce Shared Child Fixture Store');
    $storeChild->setData('url_key', 'fixturestore-athoscommerce-grouped-shared-child-1');
    $productRepository->save($storeChild);
} catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
    // Fixture store is not available in this context; ignore store-specific overrides.
}
