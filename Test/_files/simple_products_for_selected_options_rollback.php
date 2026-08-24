<?php

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;

$objectManager = Bootstrap::getObjectManager();
/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

$skus = [
    'simple-red-m',
    'simple-blue-s'
];

foreach ($skus as $sku) {
    try {
        $product = $productRepository->get($sku);
        $productRepository->delete($product);
    } catch (NoSuchEntityException $e) {
        // ignore
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
