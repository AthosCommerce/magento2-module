<?php
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Link;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

/** @var StockRegistryInterface $stockRegistry */
$stockRegistry = $objectManager->get(StockRegistryInterface::class);

/** @var WriterInterface $configWriter */
$configWriter = $objectManager->get(WriterInterface::class);

/**
 * Force legacy-stock-friendly config
 */
$configWriter->save('cataloginventory/options/manage_stock', 1, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
$configWriter->save('cataloginventory/item_options/manage_stock', 1, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
$configWriter->save('cataloginventory/item_options/backorders', 0, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);

$createSimple = static function (
    string $sku,
    string $name,
    float $price,
    float $qty,
    int $visibility = Visibility::VISIBILITY_BOTH
) use ($productRepository, $stockRegistry): Product {
    /** @var Product $product */
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

    /** @var Product $saved */
    $saved = $productRepository->save($product);

    $stockItem = $stockRegistry->getStockItem((int)$saved->getId());
    $stockItem->setUseConfigManageStock(false);
    $stockItem->setManageStock(true);
    $stockItem->setIsInStock(true);
    $stockItem->setQty($qty);
    $stockRegistry->updateStockItemBySku($sku, $stockItem);

    return $saved;
};

$createGrouped = static function (
    string $sku,
    string $name,
    float $qty
) use ($productRepository, $stockRegistry): Product {
    /** @var Product $product */
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

    /** @var Product $saved */
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
    'athoscommerce_grouped_shared_stock_child_legacy_1',
    'AthosCommerce Shared Stock Child Legacy 1',
    25.00,
    7.0,
    Visibility::VISIBILITY_BOTH
);

$parent1 = $createGrouped(
    'athoscommerce_grouped_shared_stock_parent_legacy_1',
    'AthosCommerce Shared Stock Parent Legacy 1',
    11.0
);

$parent2 = $createGrouped(
    'athoscommerce_grouped_shared_stock_parent_legacy_2',
    'AthosCommerce Shared Stock Parent Legacy 2',
    29.0
);

/**
 * Attach the same child to both grouped parents
 */
$link1 = Bootstrap::getObjectManager()->create(Link::class);
$link1->setSku($parent1->getSku());
$link1->setLinkType('associated');
$link1->setLinkedProductSku($sharedChild->getSku());
$link1->setLinkedProductType($sharedChild->getTypeId());
$link1->setPosition(1);
$parent1->setProductLinks([$link1]);
$productRepository->save($parent1);

$link2 = Bootstrap::getObjectManager()->create(Link::class);
$link2->setSku($parent2->getSku());
$link2->setLinkType('associated');
$link2->setLinkedProductSku($sharedChild->getSku());
$link2->setLinkedProductType($sharedChild->getTypeId());
$link2->setPosition(1);
$parent2->setProductLinks([$link2]);
$productRepository->save($parent2);

/**
 * Re-apply stock after link save, to avoid any stock side effects from product resave.
 */
foreach ([
             ['sku' => 'athoscommerce_grouped_shared_stock_child_legacy_1', 'qty' => 7.0],
             ['sku' => 'athoscommerce_grouped_shared_stock_parent_legacy_1', 'qty' => 11.0],
             ['sku' => 'athoscommerce_grouped_shared_stock_parent_legacy_2', 'qty' => 29.0],
         ] as $stockRow) {
    $product = $productRepository->get($stockRow['sku'], false, null, true);
    $stockItem = $stockRegistry->getStockItem((int)$product->getId());
    $stockItem->setUseConfigManageStock(false);
    $stockItem->setManageStock(true);
    $stockItem->setIsInStock(true);
    $stockItem->setQty($stockRow['qty']);
    $stockRegistry->updateStockItemBySku($stockRow['sku'], $stockItem);
}
