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
use Magento\Swatches\Helper\Data as SwatchHelper;
use Magento\Store\Model\StoreManagerInterface;

class SwatchOptionsProvider implements DataProviderInterface
{
    /**
     * @var SwatchHelper
     */
    protected $swatchHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Configurable
     */
    private $configurableType;

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
     * @param SwatchHelper $swatchHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        DataProvider             $provider,
        AthosCommerceLogger      $logger,
        ParentDataContextManager $parentProductContextManager,
        Configurable             $configurableType,
        SwatchHelper             $swatchHelper,
        StoreManagerInterface    $storeManager
    )
    {
        $this->provider = $provider;
        $this->logger = $logger;
        $this->parentProductContextManager = $parentProductContextManager;
        $this->configurableType = $configurableType;
        $this->swatchHelper = $swatchHelper;
        $this->storeManager = $storeManager;
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
        $ignoredFields = $feedSpecification->getIgnoreFields();

        if (empty($feedSpecification->getSwatchOptionFieldsNames()) || in_array('swatchOptionSourceFieldNames', $ignoredFields)) {
            return $products;
        }

        $swatch = [];
        if ($feedSpecification->getSwatchOptionFieldsNames() && !in_array('swatchOptionSourceFieldNames', $ignoredFields)) {
            $swatch = $feedSpecification->getSwatchOptionFieldsNames();
        }


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

            if (!$parentProduct) {
                continue;
            }
            // todo  performance check pending
            if (is_array($parentProduct)) {
                $parentProduct = $parentProduct[0] ?? null;
            }

            if ($parentProduct instanceof \Magento\Catalog\Model\Product) {
                $configurableAttributes = $parentProduct->getTypeInstance()->getConfigurableAttributes($parentProduct);

                $swatchOptions = [];

                foreach ($configurableAttributes as $attribute) {
                    $attr = $attribute->getProductAttribute();
                    if (!$attr) continue;

                    $attrCode = $attr->getAttributeCode();
                    $attrLabel = $attr->getStoreLabel();
                    $defaultValue = $attribute->getProductAttribute()->getDefaultValue();
                    $simpleValue = $productModel->getAttributeText($attrCode);

                    $optionId = $productModel->getData($attrCode);

                    $this->logger->info('Processing attribute', [
                        'sku' => $productModel->getSku(),
                        'attr_code' => $attrCode,
                        'simple_value' => $simpleValue,
                        'option_id' => $optionId
                    ]);

                    if (!$simpleValue) continue;

                    // Check against feedSpecification array
                    if (!in_array($attrCode, $swatch)) {
                        $this->logger->info('Skipping attribute because it is not in swatch array', [
                            'sku' => $productModel->getSku(),
                            'attr_code' => $attrCode
                        ]);
                        continue;
                    }

                    $entry = [
                        'label' => $attrLabel,
                        'value' => $simpleValue,
                        'default' => $defaultValue
                    ];

                    if ($optionId) {
                        $swatchInfo = $this->swatchHelper->getSwatchesByOptionsId([$optionId]);
                        $swatchDetail = $swatchInfo[$optionId] ?? [];

                        if ($swatchDetail) {
                            $entry['id'] = $optionId;
                            $entry['colors'] = isset($swatchDetail['value']) ? [$swatchDetail['value']] : [];
                            $entry['image'] = isset($swatchDetail['thumbnail'])
                                ? $this->storeManager->getStore()->getBaseUrl(
                                    \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                                ) . 'attribute/swatch/' . $swatchDetail['thumbnail']
                                : null;
                        }
                    }

                    $swatchOptions[$attrCode] = $entry;
                }

                $product['__swatch_options'] = $swatchOptions;
            }
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
