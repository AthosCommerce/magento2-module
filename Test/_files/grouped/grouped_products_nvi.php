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

use Magento\Catalog\Api\Data\ProductLinkInterface;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var ProductLinkInterfaceFactory $productLinkFactory */
$productLinkFactory = Bootstrap::getObjectManager()->get(ProductLinkInterfaceFactory::class);
$productIds = ['1000', '1001'];

foreach ($productIds as $productId) {
    /** @var $product Product */
    $product = $objectManager->create(Product::class);
    $product->setTypeId(Type::TYPE_SIMPLE)
        ->setWebsiteIds([1])
        ->setAttributeSetId(4)
        ->setName('AthosCommerce Grouped Test Simple ' . $productId)
        ->setSku('athoscommerce_grouped_test_simple_' . $productId)
        ->setPrice((int) $productId)
        ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
        ->setStatus(Status::STATUS_ENABLED)
        ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
        ->setData('boolean_attribute', true)
        ->setData('decimal_attribute', $productId);

    $linkedProducts[] = $productRepository->save($product);
}

/** @var $product Product */
$product = $objectManager->create(Product::class);

$product->setTypeId(Grouped::TYPE_CODE)
    ->setWebsiteIds([1])
    ->setAttributeSetId(4)
    ->setName('AthosCommerce Grouped Test Grouped Product')
    ->setSku('athoscommerce_grouped_test_grouped_1')
    ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
    ->setStatus(Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1]);

foreach ($linkedProducts as $linkedProduct) {
    /** @var ProductLinkInterface $productLink */
    $productLink = $productLinkFactory->create();
    $productLink->setSku($product->getSku())
        ->setLinkType('associated')
        ->setLinkedProductSku($linkedProduct->getSku())
        ->setLinkedProductType($linkedProduct->getTypeId())
        ->getExtensionAttributes()
        ->setQty(1);
    $newLinks[] = $productLink;
}

$product->setProductLinks($newLinks);

$productRepository->save($product);
$newLinks = [];
$linkedProducts = [];
$productIds = ['1010', '1011', '1012', '1013'];

foreach ($productIds as $productId) {
    /** @var $product Product */
    $product = $objectManager->create(Product::class);
    $product->setTypeId(Type::TYPE_SIMPLE)
        ->setWebsiteIds([1])
        ->setAttributeSetId(4)
        ->setName('AthosCommerce Grouped 2 Test Simple ' . $productId)
        ->setSku('athoscommerce_grouped_test_simple_' . $productId)
        ->setPrice((int) $productId)
        ->setVisibility(Visibility::VISIBILITY_BOTH)
        ->setStatus(Status::STATUS_ENABLED)
        ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
        ->setData('boolean_attribute', false)
        ->setData('decimal_attribute', $productId);

    $linkedProducts[] = $productRepository->save($product);
}

/** @var $product Product */
$product = $objectManager->create(Product::class);

$product->setTypeId(Grouped::TYPE_CODE)
    ->setWebsiteIds([1])
    ->setAttributeSetId(4)
    ->setName('AthosCommerce Grouped Test Grouped Product 2')
    ->setSku('athoscommerce_grouped_test_grouped_2')
    ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
    ->setStatus(Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1]);

foreach ($linkedProducts as $linkedProduct) {
    /** @var ProductLinkInterface $productLink */
    $productLink = $productLinkFactory->create();
    $productLink->setSku($product->getSku())
        ->setLinkType('associated')
        ->setLinkedProductSku($linkedProduct->getSku())
        ->setLinkedProductType($linkedProduct->getTypeId())
        ->getExtensionAttributes()
        ->setQty(1);
    $newLinks[] = $productLink;
}

$product->setProductLinks($newLinks);

$productRepository->save($product);
