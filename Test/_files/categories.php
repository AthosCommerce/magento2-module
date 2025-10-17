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

use Magento\Catalog\Model\CategoryFactory;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var CategoryFactory $categoryFactory */
$categoryFactory = $objectManager->get(CategoryFactory::class);
$categories = [
    [
        'id' => 1000,
        'name' => 'Category 1000',
        'parent_id' => 2,
        'path' => '1/2/1000',
        'level' => 2,
        'available_sort_by' => 'name',
        'default_sort_by' => 'name',
        'is_active' => true,
        'position' => 1,
    ],
    [
        'id' => 1001,
        'name' => 'Category 1000.1001',
        'parent_id' => 1000,
        'path' => '1/2/1000/1001',
        'level' => 3,
        'available_sort_by' => 'name',
        'default_sort_by' => 'name',
        'is_active' => true,
        'position' => 1
    ],
    [
        'id' => 1002,
        'name' => 'Category 1000.1001.1002',
        'parent_id' => 1001,
        'path' => '1/2/1000/1001/1002',
        'level' => 4,
        'available_sort_by' => 'name',
        'default_sort_by' => 'name',
        'is_active' => true,
        'position' => 1
    ],
    [
        'id' => 1010,
        'name' => 'Category 1010',
        'parent_id' => 2,
        'path' => '1/2/1010',
        'level' => 2,
        'available_sort_by' => 'name',
        'default_sort_by' => 'name',
        'is_active' => true,
        'position' => 2,
    ],
    [
        'id' => 1011,
        'name' => 'Category 1010.1011',
        'parent_id' => 1010,
        'path' => '1/2/1010/1011',
        'level' => 3,
        'available_sort_by' => 'name',
        'default_sort_by' => 'name',
        'is_active' => false,
        'position' => 1
    ],
    [
        'id' => 1012,
        'name' => 'Category 1010.1011.1012',
        'parent_id' => 1011,
        'path' => '1/2/1010/1011/1012',
        'level' => 4,
        'available_sort_by' => 'name',
        'default_sort_by' => 'name',
        'is_active' => true,
        'position' => 1
    ],
    [
        'id' => 1020,
        'name' => 'Category 1020',
        'parent_id' => 2,
        'path' => '1/2/1020',
        'level' => 2,
        'available_sort_by' => 'name',
        'default_sort_by' => 'name',
        'is_active' => false,
        'position' => 3,
    ],
    [
        'id' => 1021,
        'name' => 'Category 1020.1021',
        'parent_id' => 1020,
        'path' => '1/2/1020/1021',
        'level' => 3,
        'available_sort_by' => 'name',
        'default_sort_by' => 'name',
        'is_active' => false,
        'position' => 1
    ],
];

foreach ($categories as $data) {
    /** @var \Magento\Catalog\Model\Category $model */
    $model = $categoryFactory->create();
    $model->isObjectNew(true);
    $model->setId($data['id'])
        ->setName($data['name'])
        ->setParentId($data['parent_id'])
        ->setPath($data['path'])
        ->setLevel($data['level'])
        ->setAvailableSortBy($data['available_sort_by'])
        ->setDefaultSortBy($data['default_sort_by'])
        ->setIsActive($data['is_active'])
        ->setPosition($data['position'])
        ->setStoreId(0)
        ->save();
}
