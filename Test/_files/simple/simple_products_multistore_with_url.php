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

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

require __DIR__ . '/simple_products_multistore.php';
$storeManager = Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->get(StoreManagerInterface::class);
$product = Bootstrap::getObjectManager()->create(Product::class);
$productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
$currentStoreId = $storeManager->getStore()->getId();
$secondStoreId = $storeManager->getStore('fixturestore')->getId();
try {
    $storeManager->setCurrentStore($secondStoreId);
    $product = $productRepository->get('athoscommerce_simple_1');
    $product->setStoreId($secondStoreId)
        ->setUrlKey('fixturestore-athoscommerce-simple-1');
    $productRepository->save($product);
    $product = $productRepository->get('athoscommerce_simple_2');
    $product->setStoreId($secondStoreId)
        ->setUrlKey('fixturestore-athoscommerce-simple-2');
    $productRepository->save($product);
} finally {
    $storeManager->setCurrentStore($currentStoreId);
}
