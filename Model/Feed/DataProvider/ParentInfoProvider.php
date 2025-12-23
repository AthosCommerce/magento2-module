<?php

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\DataProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentDataContextManager;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\LocalizedException;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;

class ParentInfoProvider implements DataProviderInterface
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;

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
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        DataProvider $provider,
        AthosCommerceLogger $logger,
        ParentDataContextManager $parentProductContextManager,
        Configurable $configurableType,
        MetadataPool $metadataPool
    ) {
        $this->provider = $provider;
        $this->logger = $logger;
        $this->parentProductContextManager = $parentProductContextManager;
        $this->configurableType = $configurableType;
        $this->metadataPool = $metadataPool;
    }

    /**
     * Returns parent_image JSON for configurable product
     *
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     * @throws LocalizedException
     * @throws \Exception
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $ignoredFields = $feedSpecification->getIgnoreFields();
        if (in_array('__parent_id', $ignoredFields)
            && in_array('__parent_image', $ignoredFields)
            && in_array('__parent_title', $ignoredFields)
            && in_array('linked_field', $ignoredFields)
        ) {
            return $products;
        }

        $this->logger->info('Generating ParentInfoProvider JSON for configurable products', [
            'method' => __METHOD__,
            'format' => $feedSpecification->getFormat(),
        ]);

        foreach ($products as &$product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            $parent_image = null;
            $parent_title = null;
            $parentLinkId = null;

            if ($productModel->getTypeId() === 'simple') {
                $parentIds = $this->configurableType->getParentIdsByChild($productModel->getId());
                if (!empty($parentIds)) {
                    $parentId = (int)$parentIds[0];
                    $parentProduct = $this->parentProductContextManager->getParentsDataByProductId($parentId);
                    if (is_array($parentProduct)) {
                        $parentProduct = $parentProduct[0] ?? null;
                    }

                    if ($parentProduct instanceof \Magento\Catalog\Model\Product) {
                        $parentLinkId = $parentProduct->getDataUsingMethod($this->getLinkField());
                        $parent_title = $parentProduct->getName();
                        $image = $parentProduct->getImage()
                            ?: $parentProduct->getSmallImage()
                                ?: $parentProduct->getThumbnail();
                        if ($image && $image !== 'no_selection') {
                            $parent_image = $parentProduct->getMediaConfig()->getMediaUrl($image);
                        }
                    }
                }
            }
            $product['__parent_id'] = $parentLinkId;
            $product['__parent_image'] = $parent_image;
            $product['__parent_title'] = $parent_title;
            $product['linked_field'] = $this->getLinkField();
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

    /**
     * @return string
     * @throws \Exception
     */
    public function getLinkField(): string
    {
        return $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
    }
}
