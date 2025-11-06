<?php

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ConfigurableProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Collection as ParentProductCollection;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\GroupedProvider;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type as MagentoProductType;
use Magento\Framework\EntityManager\MetadataPool;

class ParentInfoProvider implements DataProviderInterface
{

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var ConfigurableProvider
     */
    private $configurableProvider;
    /**
     * @var GroupedProvider
     */
    private $groupedProvider;
    /**
     * @var ParentProductCollection
     */
    private $parentProductCollection;

    /**
     * @param MagentoConfigurableType $configurable
     * @param MetadataPool $metadataPool
     * @param MagentoGroupedType $grouped
     * @param ConfigurableProvider $configurableProvider
     * @param ParentProductCollection $parentProductCollection
     */
    public function __construct(
        MetadataPool $metadataPool,
        ConfigurableProvider $configurableProvider,
        GroupedProvider $groupedProvider,
        ParentProductCollection $parentProductCollection
    ) {
        $this->metadataPool = $metadataPool;
        $this->configurableProvider = $configurableProvider;
        $this->groupedProvider = $groupedProvider;
        $this->parentProductCollection = $parentProductCollection;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     * @throws \Exception
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $childIds = $this->getChildIds($products);
        $configParentIds = $this->configurableProvider->getParentProductRelations($childIds);
        $groupedParentIds = $this->groupedProvider->getParentProductRelations($childIds);
        $parentChildIds = array_merge($configParentIds, $groupedParentIds);
        $childToParent = [];
        if (!$parentChildIds) {
            return $products;
        }

        foreach ($parentChildIds as $parentChildRow) {
            //TODO:: array check
            $childToParent[(int)$parentChildRow['product_id']] = (int)$parentChildRow['parent_id'];
        }

        $uniqueParentIds = array_values(array_unique(array_values($childToParent)));
        $parentCollection = $this->parentProductCollection->execute(
            $uniqueParentIds,
            $feedSpecification
        );

        $parentsDataById = [];
        /** @var ProductInterface $parentProduct */
        foreach ($parentCollection as $parentProduct) {
            $parentsDataById[(int)$parentProduct->getId()] = $parentProduct;
        }
        unset($parentChildIds);

        $linkField = $this->getLinkField();
        foreach ($products as &$product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }
            $childKey = (int)$productModel->getData($linkField);
            $parentId = $childToParent[$childKey] ?? null;
            if (!$parentId) {
                continue;
            }
            $parent = $parentsDataById[$parentId] ?? null;
            if (!$parent) {
                continue;
            }

            if (MagentoProductType::TYPE_SIMPLE === $productModel->getTypeId()
                || MagentoProductType::TYPE_VIRTUAL === $productModel->getTypeId()
            ) {
                //TODO::Performance if needed
                if (array_key_exists('parent_ids', $product)) {
                    $product['parent_ids'] = [$product['parent_ids'], $parentId];
                } else {
                    $product['parent_ids'] = [$parentId];
                }

                if (method_exists($parent, 'getTypeId') && $parent->getTypeId()) {
                    $product['parent_type_id'] = $parent->getTypeId();
                }
                if (method_exists($parent, 'getProductUrl') && $parent->getProductUrl()) {
                    $product['url'] = $parent->getProductUrl();
                }
                $product['visibility'] = $parent->getVisibility();
            }
        }
        unset($childToParent);
        unset($parentsDataById);

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
            //TODO:: If any product types are belongs to parents. check for composites and 3rd Party
            if (MagentoProductType::TYPE_SIMPLE === $productModel->getTypeId()
                || MagentoProductType::TYPE_VIRTUAL === $productModel->getTypeId()
            ) {
                $childIds[] = $productModel->getId();
            }
        }

        return array_filter($childIds);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getLinkField(): string
    {
        return $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        //do nothing
    }

    /**
     * @return void
     */
    public function resetAfterFetchItems(): void
    {
        // do nothing
    }
}
