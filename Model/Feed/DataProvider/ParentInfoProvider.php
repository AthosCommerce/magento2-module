<?php

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Model\Product\Type as MagentoProductType;
use Magento\Framework\EntityManager\MetadataPool;

class ParentInfoProvider implements DataProviderInterface
{
    private $metadataPool;

    /**
     * @var Configurable
     */
    private $configurable;

    /**
     * @param Configurable $configurable
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable,
        MetadataPool $metadataPool,
    ) {
        $this->configurable = $configurable;
        $this->metadataPool = $metadataPool;
    }

    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $childIds = $this->getChildIds($products);
        $parentIds = [];
        foreach ($products as &$product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }
            /*foreach ($childIds as $childId) {
                $parentIds = $this->configurable->getParentIdsByChild($childId);
                $product['parent_ids'] = $parentIds;
            }*/
            if (MagentoProductType::TYPE_SIMPLE === $productModel->getTypeId()
                || MagentoProductType::TYPE_VIRTUAL === $productModel->getTypeId()
            ) {
                 
                $product['parent_ids'] = $this->configurable->getParentIdsByChild($productModel->getData($this->getLinkField()));
                $product['product_type'] = $productModel->getTypeId();
            }
        }

        return $products;
    }

    /**
     * @param array $products
     *
     * @return array
     */
    public function getChildIds(array $products): array
    {
        $childIds = [];
        foreach ($products as $product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }
            //TODO:: If any product types are belongs to parents

        }

        return array_filter($childIds);
    }

    public function getLinkField(): string
    {
        return $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
    }

    public function reset(): void
    {
    }

    /**
     *
     */
    public function resetAfterFetchItems(): void
    {
        // do nothing
    }
}
