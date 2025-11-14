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
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

require __DIR__ . '/simple_products.php';
/** @var ObjectManager $objectManager */
$objectManager = Bootstrap::getObjectManager();
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var Product $product1 */
$product1 = $productRepository->get('athoscommerce_simple_1');
/** @var Product $product2 */
$product2 = $productRepository->get('athoscommerce_simple_2');
$product1->setSpecialPrice(5);
$product1->setSpecialFromDate(date('Y-m-d', strtotime('+3 day')));
$product1->setSpecialToDate(date('Y-m-d', strtotime('+5 day')));
$product2->setSpecialPrice(6);
$product2->setSpecialFromDate(date('Y-m-d', strtotime('-3 day')));
$product2->setSpecialToDate(date('Y-m-d', strtotime('+5 day')));
$productRepository->save($product1);
$productRepository->save($product2);
