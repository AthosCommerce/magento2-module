<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Review\Model\ResourceModel\Review\Summary\Collection;
use Magento\Review\Model\Review;
use Magento\Review\Model\Review\Summary;
use Magento\Store\Model\StoreManagerInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\Review\Model\ResourceModel\Review\Summary\CollectionFactory as SummaryCollectionFactory;

class RatingProvider implements DataProviderInterface
{
    /**
     * @var SummaryCollectionFactory
     */
    private $collectionFactory;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * RatingProvider constructor.
     *
     * @param SummaryCollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        SummaryCollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(
        array $products,
        FeedSpecificationInterface $feedSpecification
    ): array {

        $ignoredFields = $feedSpecification->getIgnoreFields();
        if (in_array('rating', $ignoredFields)
            && in_array('rating_count', $ignoredFields)
        ) {
            return $products;
        }

        $productIds = array_map(function ($product) {
            return (int)$product['entity_id'] ?? -1;
        }, $products);

        $productIds = array_unique($productIds);
        $ratings = $this->getRatings($productIds, $feedSpecification);
        foreach ($products as &$product) {
            $id = $product['entity_id'] ?? null;
            if (!$id) {
                continue;
            }

            $rating = $ratings[$id] ?? null;
            if (!$rating) {
                continue;
            }

            if (!in_array('rating', $ignoredFields)) {
                $product['rating'] = $this->convertRatingSum($rating);
            }

            if (!in_array('rating_count', $ignoredFields)) {
                $product['rating_count'] = $rating->getReviewsCount();
            }
        }

        return $products;
    }

    /**
     * @param Summary $summary
     *
     * @return float
     */
    private function convertRatingSum(Summary $summary): float
    {
        return 5 * ((int)$summary->getRatingSummary() / 100);
    }

    /**
     * @param array $productIds
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     * @throws NoSuchEntityException
     */
    private function getRatings(
        array $productIds,
        FeedSpecificationInterface $feedSpecification
    ): array {
        /** @var Collection $summaryCollection */
        $summaryCollection = $this->collectionFactory->create();
        $storeId = (int)$this->storeManager->getStore($feedSpecification->getStoreCode())->getId();
        $summaryCollection->addStoreFilter($storeId);
        $summaryCollection->getSelect()
            ->joinLeft(
                ['review_entity' => $summaryCollection->getResource()->getTable('review_entity')],
                'main_table.entity_type = review_entity.entity_id',
                'entity_code'
            )
            ->where('entity_pk_value IN (?)', $productIds)
            ->where('entity_code = ?', Review::ENTITY_PRODUCT_CODE);
        $summaryItems = $summaryCollection->getItems();
        $result = [];
        foreach ($summaryItems as $item) {
            $result[$item->getEntityPkValue()] = $item;
        }

        return $result;
    }

    /**
     *
     */
    public function reset(): void
    {
        // do nothing
    }

    /**
     *
     */
    public function resetAfterFetchItems(): void
    {
        // do nothing
    }
}
