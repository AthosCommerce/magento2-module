<?php
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Exception\LocalizedException;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;
use Magento\Catalog\Model\Product\Link;
use Magento\CatalogInventory\Api\StockRegistryInterface;

/** @var \Magento\Framework\ObjectManagerInterface $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

/** @var StockRegistryInterface $stockRegistry */
$stockRegistry = $objectManager->get(StockRegistryInterface::class);

/**
 * Create simple product helper
 */
$createSimple = static function (
    string $sku,
    string $name,
    float $price,
    int $qty,
    int $visibility = Visibility::VISIBILITY_BOTH
) use ($productRepository, $stockRegistry): Product {
    $product = Bootstrap::getObjectManager()->create(Product::class);
    $product->setTypeId('simple');
    $product->setAttributeSetId(4);
    $product->setWebsiteIds([1]);
    $product->setName($name);
    $product->setSku($sku);
    $product->setPrice($price);
    $product->setStatus(Status::STATUS_ENABLED);
    $product->setVisibility($visibility);
    $product->setTaxClassId(0);
    $product->setStockData([
        'use_config_manage_stock' => 0,
        'manage_stock' => 1,
        'is_in_stock' => 1,
        'qty' => $qty,
    ]);

    $saved = $productRepository->save($product);

    $stockItem = $stockRegistry->getStockItem((int)$saved->getId());
    $stockItem->setUseConfigManageStock(false);
    $stockItem->setManageStock(true);
    $stockItem->setIsInStock(true);
    $stockItem->setQty($qty);
    $stockRegistry->updateStockItemBySku($sku, $stockItem);

    return $saved;
};

/**
 * Create grouped product helper
 */
$createGrouped = static function (
    string $sku,
    string $name,
    int $qty
) use ($productRepository, $stockRegistry): Product {
    $product = Bootstrap::getObjectManager()->create(Product::class);
    $product->setTypeId(GroupedType::TYPE_CODE);
    $product->setAttributeSetId(4);
    $product->setWebsiteIds([1]);
    $product->setName($name);
    $product->setSku($sku);
    $product->setStatus(Status::STATUS_ENABLED);
    $product->setVisibility(Visibility::VISIBILITY_BOTH);
    $product->setTaxClassId(0);
    $product->setStockData([
        'use_config_manage_stock' => 0,
        'manage_stock' => 1,
        'is_in_stock' => 1,
        'qty' => $qty,
    ]);

    $saved = $productRepository->save($product);

    $stockItem = $stockRegistry->getStockItem((int)$saved->getId());
    $stockItem->setUseConfigManageStock(false);
    $stockItem->setManageStock(true);
    $stockItem->setIsInStock(true);
    $stockItem->setQty($qty);
    $stockRegistry->updateStockItemBySku($sku, $stockItem);

    return $saved;
};

$sharedChild = $createSimple(
    'athoscommerce_grouped_shared_stock_child_1',
    'AthosCommerce Shared Stock Child 1',
    25.00,
    7,
    Visibility::VISIBILITY_BOTH
);

$parent1 = $createGrouped(
    'athoscommerce_grouped_shared_stock_parent_1',
    'AthosCommerce Shared Stock Parent 1',
    11
);

$parent2 = $createGrouped(
    'athoscommerce_grouped_shared_stock_parent_2',
    'AthosCommerce Shared Stock Parent 2',
    29
);

/**
 * Attach the same shared child to both grouped parents
 */
$link1 = Bootstrap::getObjectManager()->create(Link::class);
$link1->setSku($parent1->getSku())
    ->setLinkType('associated')
    ->setLinkedProductSku($sharedChild->getSku())
    ->setLinkedProductType($sharedChild->getTypeId())
    ->setPosition(1);

$parent1->setProductLinks([$link1]);
$productRepository->save($parent1);

$link2 = Bootstrap::getObjectManager()->create(Link::class);
$link2->setSku($parent2->getSku())
    ->setLinkType('associated')
    ->setLinkedProductSku($sharedChild->getSku())
    ->setLinkedProductType($sharedChild->getTypeId())
    ->setPosition(1);

$parent2->setProductLinks([$link2]);
$productRepository->save($parent2);
