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
     */
    public function __construct(
        DataProvider $provider,
        LoggerInterface $logger,
        ParentDataContextManager $parentProductContextManager,
        Configurable $configurableType
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
                $this->logger->warning('Parent product missing in context', [
                    'parent_id' => $parentId
                ]);
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
