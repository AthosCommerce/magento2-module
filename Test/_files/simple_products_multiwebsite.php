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
use Magento\Catalog\Model\Product\Action;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

require __DIR__ . '/second_website_with_store_group_and_store.php';
require __DIR__ . '/simple_products.php';
$objectManager = Bootstrap::getObjectManager();
/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var Magento\Store\Model\Store $store */
$store = $storeManager->getStore('fixture_second_store');
$storeId = $store->getId();
$websiteId = $store->getWebsiteId();
/** @var Action $productAction */
$productAction = $objectManager->create(
    Action::class
);
/** @var Product $product1 */
$product1 = $productRepository->get('athoscommerce_simple_1');
/** @var Product $product2 */
$product2 = $productRepository->get('athoscommerce_simple_2');
$productAction->updateWebsites([$product2->getId()], [$websiteId], 'add');
$productAction->updateWebsites([$product1->getId()], [$websiteId], 'add');
/** @var Product $product1 */
$product1 = $productRepository->get('athoscommerce_simple_1', true, (int) $storeId, true);
/** @var Product $product2 */
$product2 = $productRepository->get('athoscommerce_simple_2', true, (int) $storeId, true);
$product1->setStoreId($storeId)
    ->setPrice(20);
$product1->save();
$product2->setStoreId($storeId)
    ->setPrice(20);
$product2->save();

$objectManager->get(IndexerRegistry::class)
    ->get('catalog_product_price')
    ->reindexAll();
