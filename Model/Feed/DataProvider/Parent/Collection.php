<?php

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Parent;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\PricesProvider;
use AthosCommerce\Feed\Model\Feed\ProductTypeIdInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogRule\Model\ResourceModel\Product\CollectionProcessor;
use Magento\Store\Model\StoreManagerInterface;

class Collection
{
    /** @var ProductCollectionFactory */
    private $collectionFactory;
    /** @var StoreManagerInterface */
    private $storeManager;
    /**
     * @var CollectionProcessor
     */
    private $collectionProcessor;
    /**
     * @var ProductTypeIdInterface
     */
    private $productTypeId;

    /**
     * @param ProductCollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessor $collectionProcessor
     * @param ProductTypeIdInterface $productTypeId
     */
    public function __construct(
        ProductCollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        CollectionProcessor $collectionProcessor,
        ProductTypeIdInterface $productTypeId
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->productTypeId = $productTypeId;
    }

    /**
     * @param array $parentEntityIds
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     */
    public function execute(
        array $parentEntityIds,
        FeedSpecificationInterface $feedSpecification
    ) {
        $store = $this->storeManager->getStore($feedSpecification->getStoreCode());
        $ignoredFields = $feedSpecification->getIgnoreFields();
        if (!$store) {
            return [];
        }
        $storeId = (int)$store->getId();
        /** @var ProductCollection $productCollection */
        $productCollection = $this->collectionFactory->create();
        $productCollection->setStore($store)
            ->addStoreFilter($store)
            ->addAttributeToSelect("*")
            ->addFieldToFilter(
                'type_id',
                [
                    'in' => $this->productTypeId->getParentTypeIdsList(),
                ]
            )
            ->addFieldToFilter('entity_id', ['in' => $parentEntityIds]);

        if (!in_array('url', $ignoredFields)) {
            $productCollection->addUrlRewrite();
        }

        if (!in_array(PricesProvider::FINAL_PRICE_KEY, $ignoredFields)
            || !in_array(PricesProvider::MAX_PRICE_KEY, $ignoredFields)
            || !in_array(PricesProvider::REGULAR_PRICE_KEY, $ignoredFields)
        ) {
            $productCollection->addPriceData();
            $this->collectionProcessor->addPriceData($productCollection);
        }

        return $productCollection;
    }
}
