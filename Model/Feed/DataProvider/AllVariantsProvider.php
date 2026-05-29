<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentRelationsContext;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\LocalizedException;

class AllVariantsProvider implements DataProviderInterface
{
    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @var ParentRelationsContext
     */
    private $parentRelationsContext;

    /**
     * @param AthosCommerceLogger $logger
     * @param ParentRelationsContext $parentRelationsContext
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        AthosCommerceLogger    $logger,
        ParentRelationsContext $parentRelationsContext,
        StockRegistryInterface $stockRegistry
    )
    {
        $this->logger = $logger;
        $this->parentRelationsContext = $parentRelationsContext;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     * @throws LocalizedException
     */
    public function getData(
        array                      $products,
        FeedSpecificationInterface $feedSpecification
    ): array
    {
        $ignoredFields = $feedSpecification->getIgnoreFields();

        if (
            in_array('__all_variants', $ignoredFields, true)
            || !$feedSpecification->getIncludeAllVariants()
        ) {
            return $products;
        }

        foreach ($products as &$product) {
            /** @var Product|null $productModel */
            $productModel = $product['product_model'] ?? null;

            if (!$productModel || !in_array($productModel->getTypeId(), ['simple', 'virtual'], true)) {
                continue;
            }

            $parentProduct = $this->parentRelationsContext->getParentsByChildId((int)$productModel->getId());

            if (!$parentProduct) {
                $product['__all_variants'] = [];
                continue;
            }

            $allVariants = [];

            if ($parentProduct->getTypeId() === 'configurable') {

                $childProducts = $parentProduct->getTypeInstance()->getUsedProducts($parentProduct);

                $configurableAttributes = $parentProduct->getTypeInstance()->getConfigurableAttributes($parentProduct);

                foreach ($childProducts as $child) {

                    $variantOptions = [];

                    foreach ($configurableAttributes as $attribute) {

                        $attr = $attribute->getProductAttribute();

                        if (!$attr) {
                            continue;
                        }

                        $attrCode = $attr->getAttributeCode();

                        $value = $child->getAttributeText($attrCode);

                        if ($value) {
                            $variantOptions[$attrCode] = ['value' => $value];
                        }
                    }

                    $allVariants[] = $this->buildVariantRow($child, $variantOptions);
                }
            } elseif ($parentProduct->getTypeId() === 'grouped') {

                $childProducts = $parentProduct->getTypeInstance()->getAssociatedProducts($parentProduct);

                foreach ($childProducts as $child) {

                    $allVariants[] = $this->buildVariantRow(
                        $child,
                        []
                    );
                }
            }

            $product['__all_variants'] = $allVariants;
        }

        return $products;
    }

    /**
     * @param Product $child
     * @param array $variantOptions
     *
     * @return array
     */
    private function buildVariantRow(
        Product $child,
        array   $variantOptions = []
    ): array
    {
        $stockItem = $this->stockRegistry->getStockItem((int)$child->getId());

        return [
            'mappings' => [
                'core' => [
                    'uid' => (int)$child->getId(),
                    'msrp' => $child->getMsrp(),
                    'price' => $child->getPrice(),
                    'url' => $child->getProductUrl(),
                ]
            ],
            'options' => $variantOptions,
            'attributes' => [
                'inventory_quantity' => (int)$stockItem->getQty(),
                'title' => !empty($variantOptions)
                    ? implode(' / ', array_column($variantOptions, 'value'))
                    : (string)$child->getName(),
                'sku' => (string)$child->getSku(),
            ]
        ];
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        //
    }

    /**
     * @return void
     */
    public function resetAfterFetchItems(): void
    {
        //
    }
}
