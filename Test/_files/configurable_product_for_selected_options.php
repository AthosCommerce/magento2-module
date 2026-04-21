<?php

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Api\LinkManagementInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\AttributeFactory;

$objectManager = Bootstrap::getObjectManager();

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

/** @var LinkManagementInterface $linkManagement */
$linkManagement = $objectManager->get(LinkManagementInterface::class);

/** @var AttributeFactory $attributeFactory */
$attributeFactory = $objectManager->get(AttributeFactory::class);

// Load simple products
$simple1 = $productRepository->get('simple-red-m');
$simple2 = $productRepository->get('simple-blue-s');

// Create configurable
$configurable = $objectManager->create(Product::class);
$configurable->setSku('configurable-test')
    ->setName('Configurable Test')
    ->setAttributeSetId(4)
    ->setTypeId(Configurable::TYPE_CODE)
    ->setStatus(1)
    ->setVisibility(4)
    ->setPrice(10);

$configurable = $productRepository->save($configurable);

$colorAttr = $configurable->getResource()->getAttribute('athos_color');
$sizeAttr = $configurable->getResource()->getAttribute('athos_size');

$configurableAttributesData = [
    [
        'attribute_id' => $colorAttr->getId(),
        'code' => 'athos_color',
        'position' => 0,
    ],
    [
        'attribute_id' => $sizeAttr->getId(),
        'code' => 'athos_size',
        'position' => 1,
    ]
];

$configurable->getTypeInstance()->setUsedProductAttributeIds(
    [$colorAttr->getId(), $sizeAttr->getId()],
    $configurable
);

$configurableAttributes = $configurable->getTypeInstance()
    ->getConfigurableAttributesAsArray($configurable);

$configurable->setCanSaveConfigurableAttributes(true);
$configurable->setConfigurableAttributesData($configurableAttributes);

$configurable = $productRepository->save($configurable);

$linkManagement->addChild($configurable->getSku(), $simple1->getSku());
$linkManagement->addChild($configurable->getSku(), $simple2->getSku());
