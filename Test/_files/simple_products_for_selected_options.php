<?php

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Eav\Api\AttributeRepositoryInterface;

$objectManager = Bootstrap::getObjectManager();

$productFactory = $objectManager->get(ProductFactory::class);
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$storeManager = $objectManager->get(StoreManagerInterface::class);
$attributeRepository = $objectManager->get(AttributeRepositoryInterface::class);

$getOptionId = function ($attributeCode, $label) use ($attributeRepository) {
    $attribute = $attributeRepository->get(Product::ENTITY, $attributeCode);
    foreach ($attribute->getSource()->getAllOptions(false) as $option) {
        if ($option['label'] === $label) {
            return $option['value'];
        }
    }
    throw new \Exception("Option not found");
};

// simple 1 → Red / M
$product = $productFactory->create();
$product->setTypeId('simple')
    ->setAttributeSetId(4)
    ->setSku('simple-red-m')
    ->setName('Simple Red M')
    ->setPrice(10)
    ->setStatus(Status::STATUS_ENABLED)
    ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
    ->setStockData(['qty' => 10, 'is_in_stock' => 1])
    ->setWebsiteIds([$storeManager->getStore()->getWebsiteId()])
    ->setData('athos_color', $getOptionId('athos_color', 'Red'))
    ->setData('athos_size', $getOptionId('athos_size', 'M'));

$productRepository->save($product);

// simple 2 → Blue / S
$product = $productFactory->create();
$product->setTypeId('simple')
    ->setAttributeSetId(4)
    ->setSku('simple-blue-s')
    ->setName('Simple Blue S')
    ->setPrice(10)
    ->setStatus(Status::STATUS_ENABLED)
    ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
    ->setStockData(['qty' => 10, 'is_in_stock' => 1])
    ->setWebsiteIds([$storeManager->getStore()->getWebsiteId()])
    ->setData('athos_color', $getOptionId('athos_color', 'Blue'))
    ->setData('athos_size', $getOptionId('athos_size', 'S'));

$productRepository->save($product);
