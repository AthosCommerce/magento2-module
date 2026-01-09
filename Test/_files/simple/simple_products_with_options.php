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

use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;

require __DIR__ . '/01_simple_products.php';

$objectManager = Bootstrap::getObjectManager();
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
$searchCriteria = $searchCriteriaBuilder->addFilter('sku', 'athoscommerce_simple%', 'like')
    ->create();

/** @var ProductCustomOptionInterfaceFactory $customOptionFactory */
$customOptionFactory = $objectManager->get(ProductCustomOptionInterfaceFactory::class);
$i = 1;
foreach ($productRepository->getList($searchCriteria)->getItems() as $product) {
    $options = [
        [
            'title' => 'test_option_code_1',
            'type' => 'field',
            'is_require' => true,
            'sort_order' => 1,
            'price' => -10.0,
            'price_type' => 'fixed',
            'sku' => 'sku1',
            'max_characters' => 10,
        ],
        [
            'title' => 'area option',
            'type' => 'area',
            'is_require' => true,
            'sort_order' => 2,
            'price' => 20.0,
            'price_type' => 'percent',
            'sku' => 'sku2',
            'max_characters' => 20
        ],
        [
            'title' => 'drop_down option ' . $i,
            'type' => 'drop_down',
            'is_require' => false,
            'sort_order' => 4,
            'values' => [
                [
                    'title' => 'drop_down option 1 ' . $i,
                    'price' => 10,
                    'price_type' => 'fixed',
                    'sku' => 'drop_down option 1 sku_' . $i,
                    'sort_order' => 1,
                ],
                [
                    'title' => 'drop_down option 2 ' . $i,
                    'price' => 20,
                    'price_type' => 'fixed',
                    'sku' => 'drop_down option 2 sku_' . $i,
                    'sort_order' => 2,
                ],
            ],
        ],
        [
            'title' => 'multiple option',
            'type' => 'multiple',
            'is_require' => false,
            'sort_order' => 5,
            'values' => [
                [
                    'title' => 'multiple option 1',
                    'price' => 10,
                    'price_type' => 'fixed',
                    'sku' => 'multiple option 1 sku',
                    'sort_order' => 1,
                ],
                [
                    'title' => 'multiple option 2',
                    'price' => 20,
                    'price_type' => 'fixed',
                    'sku' => 'multiple option 2 sku',
                    'sort_order' => 2,
                ],
            ],
        ],
        [
            'title' => 'date option',
            'type' => 'date',
            'price' => 80.0,
            'price_type' => 'fixed',
            'sku' => 'date option sku',
            'is_require' => false,
            'sort_order' => 6
        ]
    ];

    $customOptions = [];

    $sku = $product->getSku();
    foreach ($options as $option) {
        /** @var ProductCustomOptionInterface $customOption */
        $customOption = $customOptionFactory->create(['data' => $option]);
        $customOption->setProductSku($sku);

        $customOptions[] = $customOption;
    }

    $product->setOptions($customOptions);
    $productRepository->save($product);
    $i++;
}
