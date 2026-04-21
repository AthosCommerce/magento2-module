<?php

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

$objectManager = Bootstrap::getObjectManager();

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

try {
    $product = $productRepository->get('configurable-test');
    $productRepository->delete($product);
} catch (NoSuchEntityException $e) {
    // ignore
}
