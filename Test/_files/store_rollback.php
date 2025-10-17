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

use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;
use Magento\UrlRewrite\Model\UrlRewrite;

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var Store $store */
$store = $objectManager->get(Store::class);
$store->load('test', 'code');
if ($store->getId()) {
    $store->delete();
}

/** @var Store $store */
$store = $objectManager->get(Store::class);
$store->load('test', 'code');
if ($store->getId()) {
    $store->delete();
}

/** @var UrlRewriteCollectionFactory $urlRewriteCollectionFactory */
$urlRewriteCollectionFactory = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
    UrlRewriteCollectionFactory::class
);
/** @var UrlRewriteCollection $urlRewriteCollection */
$urlRewriteCollection = $urlRewriteCollectionFactory->create();
$urlRewriteCollection
    ->addFieldToFilter('store_id', ['nin' => [0, 1]]);
$urlRewrites = $urlRewriteCollection->getItems();
/** @var UrlRewrite $urlRewrite */
foreach ($urlRewrites as $urlRewrite) {
    try {
        $urlRewrite->delete();
    } catch (\Exception $exception) {
        // already removed
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
