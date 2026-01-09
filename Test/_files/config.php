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

/** @var $store Store */

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

$store = $objectManager->create(Store::class);

if (!$store->load('test', 'code')->getId()) {
    $store->setData([
        'code'       => 'test',
        'website_id' => 1,
        'group_id'   => 1,
        'name'       => 'Test Store - test',
        'sort_order' => 0,
        'is_active'  => 1,
    ]);
    $store->save();
}

$storeId = (int)$store->getId();

$resource   = $objectManager->get(ResourceConnection::class);
$connection = $resource->getConnection();
$table      = $resource->getTableName('core_config_data');


$connection->delete(
    $table,
    [
        'scope = ?'     => 'stores',
        'scope_id = ?'  => $storeId,
        'path LIKE ?'   => 'athoscommerce/%',
    ]
);

$configs = [
    [
        'scope'    => 'stores',
        'scope_id' => $storeId,
        'path'     => 'athoscommerce/indexing/enable_live_indexing',
        'value'    => '1',
    ],
    [
        'scope'    => 'stores',
        'scope_id' => $storeId,
        'path'     => 'athoscommerce/configuration/siteid',
        'value'    => 'a21sfdasdf',
    ],
    [
        'scope'    => 'stores',
        'scope_id' => $storeId,
        'path'     => 'athoscommerce/configuration/secretkey',
        'value'    => 'secretKeyMerryChristmas',
    ],
];

foreach ($configs as $config) {
    $connection->insert($table, $config);
}
