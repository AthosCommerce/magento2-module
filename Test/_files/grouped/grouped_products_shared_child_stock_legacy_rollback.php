<?php
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

$skus = [
    'athoscommerce_grouped_shared_stock_child_legacy_1',
    'athoscommerce_grouped_shared_stock_parent_legacy_1',
    'athoscommerce_grouped_shared_stock_parent_legacy_2',
];

foreach ($skus as $sku) {
    try {
        $product = $productRepository->get($sku, false, null, true);
        $productRepository->delete($product);
    } catch (NoSuchEntityException $e) {
        // ignore
    }
}
