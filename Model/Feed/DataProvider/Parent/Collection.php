<?php

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Parent;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class Collection
{
    /** @var ProductCollectionFactory */
    private $collectionFactory;
    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * @param ProductCollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ProductCollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
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
        if (!$store) {
            return [];
        }
        $storeId = (int)$store->getId();
        /** @var ProductCollection $productCollection */
        $productCollection = $this->collectionFactory->create();
        $productCollection->setStore($store)
            ->addStoreFilter($store)
            ->addAttributeToSelect(['entity_id', 'name', 'visibility', 'url_key', 'status'])
            ->addFieldToFilter('entity_id', ['in' => $parentEntityIds])
            ->addUrlRewrite()
            ->addMediaGalleryData();

        return $productCollection;
    }
}
