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

class ParentImageProvider implements DataProviderInterface
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
     * Returns parent_image JSON for configurable product
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws LocalizedException
     * @throws \Exception
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $this->logger->info('Generating ParentImageProvider JSON for configurable products', [
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

            if ($productModel->getTypeId() === 'simple') {
                $parentIds = $this->configurableType->getParentIdsByChild($productModel->getId());
                if (!empty($parentIds)) {
                    $parentId = (int)$parentIds[0];
                    $parentProduct = $this->parentProductContextManager->getParentsDataByProductId($parentId);
                    if (is_array($parentProduct)) {
                        $parentProduct = $parentProduct[0] ?? null;
                    }

                    if ($parentProduct instanceof \Magento\Catalog\Model\Product) {
                        $image = $parentProduct->getImage()
                            ?: $parentProduct->getSmallImage()
                                ?: $parentProduct->getThumbnail();
                        if ($image && $image !== 'no_selection') {
                            $parent_image = $parentProduct->getMediaConfig()->getMediaUrl($image);
                        }
                    }
                }
            }

            $product['parent_image'] = $parent_image;
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
