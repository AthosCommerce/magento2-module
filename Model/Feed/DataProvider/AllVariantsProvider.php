<?php

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\DataProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentDataContextManager;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class AllVariantsProvider implements DataProviderInterface
{
    /**
     * @var StockRegistryInterface
     */
    private  $stockRegistry;

    /**
     * @var Configurable
     */
    private  $configurableType;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;
    /**
     * @var DataProvider
     */
    private $provider;

    /**
     * @var ParentDataContextManager
     */
    private $parentProductContextManager;

    /**
     * @param DataProvider $provider
     * @param AthosCommerceLogger $logger
     * @param ParentDataContextManager $parentProductContextManager
     * @param Configurable $configurableType
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        DataProvider             $provider,
        AthosCommerceLogger          $logger,
        ParentDataContextManager $parentProductContextManager,
        Configurable             $configurableType,
        StockRegistryInterface $stockRegistry
    )
    {
        $this->provider = $provider;
        $this->logger = $logger;
        $this->parentProductContextManager = $parentProductContextManager;
        $this->configurableType = $configurableType;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * Returns __standard_options JSON for configurable product
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws LocalizedException
     * @throws \Exception
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $ignoredFields = $feedSpecification->getIgnoreFields();
        if (in_array('__all_variants', $ignoredFields) || !$feedSpecification->getIncludeAllVariants()) {
            return $products;
        }

        foreach ($products as &$product) {
            /** @var \Magento\Catalog\Model\Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel || $productModel->getTypeId() !== 'simple') {
                continue;
            }

            $parentIds = $this->configurableType->getParentIdsByChild($productModel->getId());
            if (empty($parentIds)) {
                $product['__all_variants'] = [];
                continue;
            }

            $parentId = (int)$parentIds[0];
            $parentProduct = $this->parentProductContextManager->getParentsDataByProductId($parentId);
            if (!$parentProduct) {
                continue;
            }

            $childProducts = $parentProduct->getTypeInstance()->getUsedProducts($parentProduct);
            $allVariants = [];

            foreach ($childProducts as $child) {
                $variantOptions = [];
                $configurableAttributes = $parentProduct->getTypeInstance()->getConfigurableAttributes($parentProduct);

                foreach ($configurableAttributes as $attribute) {
                    $attr = $attribute->getProductAttribute();
                    if (!$attr) continue;

                    $attrCode = $attr->getAttributeCode();
                    $value = $child->getAttributeText($attrCode);
                    if ($value) {
                        $variantOptions[$attrCode] = ['value' => $value];
                    }
                }

                $stockItem = $this->stockRegistry->getStockItem($child->getId());
                $qty = (int) $stockItem->getQty();

                $allVariants[] = [
                    'mappings' => [
                        'core' => [
                            'uid'   => $child->getId(),
                            'msrp'  => $child->getMsrp(),
                            'price' => $child->getPrice(),
                            'url'   => $child->getProductUrl(),
                        ]
                    ],
                    'options' => $variantOptions,
                    'attributes' => [
                        'inventory_quantity' => $qty,
                        'title' => implode(' / ', array_column($variantOptions, 'value'))
                    ]
                ];
            }

            $product['__all_variants'] = $allVariants;
        }

        return $products;
    }

    /**
     *
     */
    public function reset(): void
    {
        //
    }

    /**
     *
     */
    public function resetAfterFetchItems(): void
    {
        //
    }
}
