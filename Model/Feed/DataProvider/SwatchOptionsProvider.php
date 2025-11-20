<?php

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\DataProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentDataContextManager;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class SwatchOptionsProvider implements DataProviderInterface
{
    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var Configurable
     */
    private $configurableType;

    /**
     * @var LoggerInterface
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
     * @param LoggerInterface $logger
     * @param ParentDataContextManager $parentProductContextManager
     * @param Configurable $configurableType
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        DataProvider             $provider,
        LoggerInterface          $logger,
        ParentDataContextManager $parentProductContextManager,
        Configurable             $configurableType,
        StockRegistryInterface   $stockRegistry
    )
    {
        $this->provider = $provider;
        $this->logger = $logger;
        $this->parentProductContextManager = $parentProductContextManager;
        $this->configurableType = $configurableType;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * Returns __swatch_options JSON for configurable product
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws LocalizedException
     * @throws \Exception
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $this->logger->info('Returns SwatchOptionsProvider JSON for configurable product', [
            'method' => __METHOD__,
            'format' => $feedSpecification->getFormat(),
        ]);

        foreach ($products as &$product) {

            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            // Only SIMPLE products get SwatchOptionsProvider
            if ($productModel->getTypeId() !== 'simple') {
                $this->logger->debug('Skipping non-simple product', [
                    'sku' => $productModel->getSku()
                ]);
                continue;
            }

            $parentIds = $this->configurableType->getParentIdsByChild($productModel->getId());

            if (empty($parentIds)) {
                $product['standard_options'] = [];
                continue;
            }

            $parentId = (int)$parentIds[0];
            $parentProduct = $this->parentProductContextManager->getParentsDataByProductId($parentId);

            $configurableAttributes =
                $parentProduct->getTypeInstance()->getConfigurableAttributes($parentProduct);

            $swatchOptions = [
                'options' => [],
                'fields' => []
            ];

            foreach ($configurableAttributes as $attribute) {
                $attr = $attribute->getProductAttribute();
                if (!$attr) {
                    continue;
                }
                $attrCode = $attr->getAttributeCode();
                $attrLabel = $attr->getStoreLabel();
                $simpleValue = $productModel->getAttributeText($attrCode);
                if (!$simpleValue) {
                    continue;
                }
                $swatchOptions['options'][$attrCode] = [
                    'label' => $attrLabel,
                    'value' => $simpleValue,
                ];
            }

            $stockItem = $this->stockRegistry->getStockItem($productModel->getId());
            $qty = (int)$stockItem->getQty();
            $available = $stockItem->getIsInStock();
            $swatchOptions['fields'] = [
                'uid' => $productModel->getId(),
                'sku' => $productModel->getSku(),
                'msrp' => (float)$productModel->getMsrp(),
                'price' => (float)$productModel->getFinalPrice(),
                'url' => $productModel->getProductUrl(),
                'image_url' => $productModel->getMediaGalleryImages()->getFirstItem()->getUrl() ?? null,
                'quantity' => $qty,
                'title' => implode(' / ', array_column($swatchOptions['options'], 'value')),
                'available' => $available
            ];

            $product['__swatch_options'] = $swatchOptions;
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
