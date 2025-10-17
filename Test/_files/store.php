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

use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;

$store = Bootstrap::getObjectManager()->create(Store::class);
if (!$store->load('test', 'code')->getId()) {
    $store->setData(
        [
            'code' => 'test',
            'website_id' => '1',
            'group_id' => '1',
            'name' => 'Test Store',
            'sort_order' => '0',
            'is_active' => '1',
        ]
    );
    $store->save();
} else {
    if ($store->getId()) {
        /** @var Bootstrap $registry */
        $registry = Bootstrap::getObjectManager()->get(
            Registry::class
        );
        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', true);

        $store->delete();

        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', false);

        $store = Bootstrap::getObjectManager()->create(Store::class);
        $store->setData(
            [
                'code' => 'test',
                'website_id' => '1',
                'group_id' => '1',
                'name' => 'Test Store',
                'sort_order' => '0',
                'is_active' => '1',
            ]
        );
        $store->save();
    }
}
