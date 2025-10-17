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

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

require __DIR__ . '/simple_products_with_categories.php';
require __DIR__ . '/categories_second_website.php';
$objectManager = Bootstrap::getObjectManager();

/** @var WebsiteRepositoryInterface $websiteRepository */
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$website = $websiteRepository->get('test');
$websiteId = (int) $website->getId();

$skus = [
    'athoscommerce_simple_1' => [1000, 1001, 1002, 1011, 1012, 2000, 2001],
    'athoscommerce_simple_2' => [1002, 1012, 1020, 1021, 2000, 2001]
];
/** @var CategoryLinkManagementInterface $categoryLinkManagement */
$categoryLinkManagement = $objectManager->get(CategoryLinkManagementInterface::class);

foreach ($skus as $sku => $categoryIds) {
    $product = $productRepository->get($sku, true);
    $product->setWebsiteIds(array_merge($product->getWebsiteIds(), [$websiteId]));
    $productRepository->save($product);
    $categoryLinkManagement->assignProductToCategories($sku, $categoryIds);
}
