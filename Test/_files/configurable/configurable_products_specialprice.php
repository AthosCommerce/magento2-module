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

require __DIR__ . '/configurable_products.php';
/** @var ObjectManager $objectManager */
$objectManager = Bootstrap::getObjectManager();
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var Product $product10 */
$product10 = $productRepository->get('athoscommerce_configurable_test_simple_10');
/** @var Product $product20 */
$product20 = $productRepository->get('athoscommerce_configurable_test_simple_20');
/** @var Product $product30 */
$product30 = $productRepository->get('athoscommerce_configurable_test_simple_30');
/** @var Product $product40 */
$product40 = $productRepository->get('athoscommerce_configurable_test_simple_40');
$product10->setSpecialPrice(6);
$product10->setSpecialFromDate(date('Y-m-d', strtotime('-3 day')));
$product10->setSpecialToDate(date('Y-m-d', strtotime('+5 day')));
$product20->setSpecialPrice(2);
$product20->setSpecialFromDate(date('Y-m-d', strtotime('-4 day')));
$product20->setSpecialToDate(date('Y-m-d', strtotime('-2 day')));
$product30->setSpecialPrice(20);
$product30->setSpecialFromDate(date('Y-m-d', strtotime('+3 day')));
$product30->setSpecialToDate(date('Y-m-d', strtotime('+5 day')));
$product40->setSpecialPrice(25);
$product40->setSpecialFromDate(date('Y-m-d', strtotime('-3 day')));
$product40->setSpecialToDate(date('Y-m-d', strtotime('+5 day')));
$productRepository->save($product10);
$productRepository->save($product20);
$productRepository->save($product30);
$productRepository->save($product40);
