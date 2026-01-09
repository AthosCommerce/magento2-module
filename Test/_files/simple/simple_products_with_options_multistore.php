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
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

require __DIR__ . '/simple_products_with_options.php';

$objectManager = Bootstrap::getObjectManager();
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
$searchCriteria = $searchCriteriaBuilder->addFilter('sku', 'athoscommerce_simple%', 'like')
    ->create();

/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
$storeId = $storeManager->getStore('default')->getId();
/** @var Product $product */
foreach ($productRepository->getList($searchCriteria)->getItems() as $product) {
    $options = $product->getProductOptionsCollection()->getItems();
    $sku = $product->getSku();
    foreach ($options as $option) {
        if ($option->getType() == 'drop_down') {
            $option->setTitle('Store Default ' . $option->getTitle());
            foreach ($option->getValues() as $value) {
                $value->setTitle('Store Default ' . $value->getTitle());
            }
        }
        $option->setProductSku($sku);
    }

    $product->setStoreId((int) $storeId);
    $product->setOptions($options);
    $productRepository->save($product);
}
