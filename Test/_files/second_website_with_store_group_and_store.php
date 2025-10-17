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

declare(strict_types=1);

use Magento\Catalog\Helper\DefaultCategory;
use Magento\CatalogSearch\Model\Indexer\Fulltext;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\GroupInterfaceFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\StoreInterfaceFactory;
use \Magento\Store\Api\Data\WebsiteInterface;
use \Magento\Store\Api\Data\WebsiteInterfaceFactory;
use Magento\Store\Model\ResourceModel\Group as GroupResource;
use Magento\Store\Model\ResourceModel\Store as StoreResource;
use Magento\Store\Model\ResourceModel\Website as WebsiteResource;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
/** @var WebsiteResource $websiteResource */
$websiteResource = $objectManager->get(WebsiteResource::class);
/** @var StoreResource $storeResource */
$storeResource = $objectManager->get(StoreResource::class);
/** @var GroupResource $groupResource */
$groupResource = $objectManager->get(GroupResource::class);
/** @var DefaultCategory $defaultCategory */
$defaultCategory = $objectManager->get(DefaultCategory::class);
/** @var WebsiteInterface $website */
$website = $objectManager->get(WebsiteInterfaceFactory::class)->create();
$website->setCode('test')->setName('Test Website');
$websiteResource->save($website);
/** @var GroupInterface $storeGroup */
$storeGroup = $objectManager->get(GroupInterfaceFactory::class)->create();
$storeGroup->setCode('second_group')
    ->setRootCategoryId($defaultCategory->getId())
    ->setName('second store group')
    ->setWebsite($website);
$groupResource->save($storeGroup);
/* Refresh stores memory cache */
$storeManager->reinitStores();

/** @var StoreInterface $store */
$store = $objectManager->get(StoreInterfaceFactory::class)->create();
$store->setCode('fixture_second_store')
    ->setWebsiteId($website->getId())
    ->setGroupId($storeGroup->getId())
    ->setName('Fixture Second Store')
    ->setSortOrder(10)
    ->setIsActive(1);
$storeResource->save($store);
/* Refresh CatalogSearch index */
/** @var IndexerRegistry $indexerRegistry */
$storeManager->reinitStores();
$indexerRegistry = $objectManager->get(IndexerRegistry::class);
$indexerRegistry->get(Fulltext::INDEXER_ID)->reindexAll();
