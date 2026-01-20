<?php

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\DataProvider as ConfigurableDataProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentDataContextManager;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Throwable;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;

class StandardOptionsProvider implements DataProviderInterface
{
    /**
     * @var Configurable
     */
    private $configurableType;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @var ConfigurableDataProvider
     */
    private $provider;

    /**
     * @var ParentDataContextManager
     */
    private $parentProductContextManager;

    /**
     * @param ConfigurableDataProvider $provider
     * @param AthosCommerceLogger $logger
     * @param ParentDataContextManager $parentProductContextManager
     * @param Configurable $configurableType
     */
    public function __construct(
        ConfigurableDataProvider $provider,
        AthosCommerceLogger      $logger,
        ParentDataContextManager $parentProductContextManager,
        Configurable             $configurableType
    )
    {
        $this->provider = $provider;
        $this->logger = $logger;
        $this->parentProductContextManager = $parentProductContextManager;
        $this->configurableType = $configurableType;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws LocalizedException
     * @throws Throwable
     */
    public function getData(
        array                      $products,
        FeedSpecificationInterface $feedSpecification
    ): array
    {
        foreach ($products as &$product) {

            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            // Only SIMPLE products get __standard_options
            if ($productModel->getTypeId() !== 'simple') {
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
                $this->logger->warning(
                    '[StandardOptions] Parent product missing in context',
                    [
                        'productId' => $productModel->getId(),
                        'parentIds' => $parentIds,
                        'method' => __METHOD__
                    ]
                );
                continue;
            }
            // todo  performance check pending
            if (is_array($parentProduct)) {
                $parentProduct = $parentProduct[0] ?? null;
            }

            if ($parentProduct instanceof \Magento\Catalog\Model\Product) {
                $configurableAttributes = $parentProduct->getTypeInstance()->getConfigurableAttributes($parentProduct);

                $standardOptions = [];

                foreach ($configurableAttributes as $attribute) {
                    $attr = $attribute->getProductAttribute();
                    if (!$attr) {
                        continue;
                    }
                    $attrCode = $attr->getAttributeCode();
                    $attrLabel = $attr->getStoreLabel();
                    // Selected value for this simple product
                    $value = $productModel->getAttributeText($attrCode);
                    if (!$value) {
                        continue;
                    }

                    $standardOptions[$attrCode] = [
                        'label' => $attrLabel,
                        'value' => $value
                    ];
                }
                $product['__standard_options'] = $standardOptions;
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
