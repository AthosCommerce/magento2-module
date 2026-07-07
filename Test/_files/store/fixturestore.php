<?php
declare(strict_types=1);

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var StoreRepositoryInterface $storeRepository */
$storeRepository = $objectManager->get(StoreRepositoryInterface::class);
/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);

$storeCode = 'fixturestore';

try {
    $storeRepository->get($storeCode);
    return;
} catch (NoSuchEntityException $e) {
    // create store view
}

$defaultStore = $storeManager->getDefaultStoreView();

/** @var Store $store */
$store = $objectManager->create(Store::class);
$store->setCode($storeCode);
$store->setWebsiteId((int)$defaultStore->getWebsiteId());
$store->setGroupId((int)$defaultStore->getStoreGroupId());
$store->setName('Fixture Store');
$store->setSortOrder(10);
$store->setIsActive(1);
$store->save();
