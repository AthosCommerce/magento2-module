<?php
namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\DataProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentDataContextManager;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Throwable;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;


class StandardOptionsProvider implements DataProviderInterface
{
    private Configurable $configurableType;

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
     */
    public function __construct(
        DataProvider $provider,
        LoggerInterface $logger,
        ParentDataContextManager $parentProductContextManager,
        Configurable $configurableType,
    ) {
        $this->provider = $provider;
        $this->logger = $logger;
        $this->parentProductContextManager = $parentProductContextManager;
        $this->configurableType = $configurableType;
    }

    /**
     * Returns standard_options JSON for configurable product
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws LocalizedException
     * @throws \Exception
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $this->logger->info('Returns __standard_options JSON for configurable product', [
            'method' => __METHOD__,
            'format' => $feedSpecification->getFormat(),
        ]);
        foreach ($products as &$product) {

            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

//            $this->logger->debug('Processing product in __standard_options generator', [
//                'product_id' => $productModel->getId(),
//                'sku'        => $productModel->getSku(),
//                'type'       => $productModel->getTypeId()
//            ]);

            // Only SIMPLE products get __standard_options
            if ($productModel->getTypeId() !== 'simple') {
                $this->logger->debug('Skipping non-simple product', [
                    'sku' => $productModel->getSku()
                ]);
                continue;
            }


            $parentIds = $this->configurableType->getParentIdsByChild($productModel->getId());

//            $this->logger->debug('Parent check for simple product', [
//                'child_id'   => $productModel->getId(),
//                'child_sku'  => $productModel->getSku(),
//                'parent_ids' => $parentIds
//            ]);

            if (empty($parentIds)) {
                $this->logger->debug('Simple product has no configurable parent', [
                    'sku' => $productModel->getSku()
                ]);

                $product['standard_options'] = [];
                continue;
            }

            $parentId = (int)$parentIds[0];


            $parentProduct = $this->parentProductContextManager->getParentsDataByProductId($parentId);

            if (!$parentProduct) {
                $this->logger->warning('Parent product missing in context', [
                    'parent_id' => $parentId
                ]);
            }


            $configurableAttributes =
                $parentProduct->getTypeInstance()->getConfigurableAttributes($parentProduct);

//            $this->logger->debug('Configurable attribute metadata', [
//                'parent_sku'    => $parentProduct->getSku(),
//                'attribute_cnt' => count($configurableAttributes)
//            ]);


            $standardOptions = [];


            foreach ($configurableAttributes as $attribute) {

                $attr = $attribute->getProductAttribute();
                if (!$attr) {
                    $this->logger->warning('Attribute instance missing for parent', [
                        'parent_sku' => $parentProduct->getSku()
                    ]);
                    continue;
                }

                $attrCode  = $attr->getAttributeCode();
                $attrLabel = $attr->getStoreLabel();

//                $this->logger->debug('Processing attribute', [
//                    'child_sku' => $productModel->getSku(),
//                    'attribute_code'  => $attrCode,
//                    'attribute_label' => $attrLabel,
//                ]);

                // Selected value for this simple product
                $value = $productModel->getAttributeText($attrCode);

                if (!$value) {
                    $this->logger->debug('Simple product missing attribute value', [
                        'child_sku' => $productModel->getSku(),
                        'attribute_code' => $attrCode
                    ]);
                    continue;
                }

                $standardOptions[$attrCode] = [
                    'label' => $attrLabel,
                    'value' => $value
                ];

//                $this->logger->debug('Added __standard_option value', [
//                    'child_sku' => $productModel->getSku(),
//                    'attribute_code' => $attrCode,
//                    'selected_value' => $value
//                ]);
            }


            $product['__standard_options'] = $standardOptions;

            $this->logger->debug('Final __standard_options generated', [
                'child_sku' => $productModel->getSku(),
                'options'   => $standardOptions
            ]);
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
