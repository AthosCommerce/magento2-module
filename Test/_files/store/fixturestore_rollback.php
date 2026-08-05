<?php
declare(strict_types=1);

use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var Store $store */
$store = $objectManager->create(Store::class)->load('fixturestore', 'code');
if (!$store->getId()) {
    return;
}

try {
    $store->delete();
} catch (\Throwable $e) {
    // ignore cleanup issues
}
