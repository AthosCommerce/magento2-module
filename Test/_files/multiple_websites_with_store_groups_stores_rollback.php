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

/** @var Registry $registry */

use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;

$registry = Bootstrap::getObjectManager()->get(Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);
/** Delete the second website **/
$website = Bootstrap::getObjectManager()->create(Website::class);
/** @var $website Website */
$websiteId = $website->load('second', 'code')->getId();
if ($websiteId) {
    $website->delete();
}
$website2 = Bootstrap::getObjectManager()->create(Website::class);
/** @var $website Website */
$websiteId2 = $website2->load('third', 'code')->getId();
if ($websiteId2) {
    $website2->delete();
}

$store = Bootstrap::getObjectManager()->create(Store::class);
if ($store->load('second_store_view', 'code')->getId()) {
    $store->delete();
}

$store2 = Bootstrap::getObjectManager()->create(Store::class);
if ($store2->load('third_store_view', 'code')->getId()) {
    $store2->delete();
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
