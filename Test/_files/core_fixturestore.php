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

use Magento\Store\Model\ResourceModel\Store as StoreResource;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
/** @var StoreFactory $storeFactory */
$storeFactory = $objectManager->get(StoreFactory::class);
/** @var StoreResource $storeResource */
$storeResource = $objectManager->get(StoreResource::class);
$storeCode = 'fixturestore';

$store = $storeFactory->create();
$store->setCode($storeCode)
    ->setWebsiteId($storeManager->getWebsite()->getId())
    ->setGroupId($storeManager->getWebsite()->getDefaultGroupId())
    ->setName('Fixture Store')
    ->setSortOrder(10)
    ->setIsActive(1);
$storeResource->save($store);

$storeManager->reinitStores();
//if test using this fixture relies on full text functionality it is required to explicitly perform re-indexation
