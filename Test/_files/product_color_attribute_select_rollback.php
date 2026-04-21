<?php

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Model\Product;

$objectManager = Bootstrap::getObjectManager();

/** @var AttributeRepositoryInterface $attributeRepository */
$attributeRepository = $objectManager->get(AttributeRepositoryInterface::class);

try {
    $attribute = $attributeRepository->get(Product::ENTITY, 'athos_color');
    $attributeRepository->delete($attribute);
} catch (NoSuchEntityException $e) {
    // Attribute does not exist, nothing to delete
}
