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
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;

require __DIR__ . '/core_fixturestore.php';
require __DIR__ . '/simple_products_with_images.php';

$objectManager = Bootstrap::getObjectManager();
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
/** @var Store $store */
$store = $objectManager->create(Store::class);
$store->load('fixturestore', 'code');
/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
$searchCriteria = $searchCriteriaBuilder->addFilter('sku', 'athoscommerce_simple%', 'like')
    ->create();
foreach ($productRepository->getList($searchCriteria)->getItems() as $product) {
    $product->setStoreId($store->getId());
    $mediaGallery = $product->getMediaGallery();
    foreach ($mediaGallery['images'] as &$imageData) {
        $imageData['label'] = 'Store fixturestore ' . $imageData['label'];
    }

    $product->setMediaGallery($mediaGallery);
    $product->save();
}
