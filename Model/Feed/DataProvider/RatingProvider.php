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

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentRelationsContext;
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
     * @var ParentRelationsContext
     */
    private $parentRelationsContext;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * RatingProvider constructor.
     *
     * @param SummaryCollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     * @param ParentRelationsContext $parentRelationsContext
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        SummaryCollectionFactory $collectionFactory,
        StoreManagerInterface    $storeManager,
        ParentRelationsContext   $parentRelationsContext,
        AthosCommerceLogger      $logger
    )
    {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->parentRelationsContext = $parentRelationsContext;
        $this->logger = $logger;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(
        array                      $products,
        FeedSpecificationInterface $feedSpecification
    ): array
    {

        $ignoredFields = $feedSpecification->getIgnoreFields();
        if (in_array('rating', $ignoredFields, true)
            && in_array('rating_count', $ignoredFields, true)
        ) {
            return $products;
        }

        $productIds = array_map(function ($product) {
            return (int)$product['entity_id'] ?? -1;
        }, $products);

        $productIds = array_values(array_unique($productIds));

        $ratings = $this->getRatings($productIds, $feedSpecification);

        $resolvedRatings = $this->getRatingSummaryWithParents(
            $productIds,
            $ratings,
            $feedSpecification
        );
        foreach ($products as &$product) {
            $productId = $product['entity_id'] ?? null;
            if (!$productId || empty($resolvedRatings[$productId])) {
                continue;
            }

            $summary = $resolvedRatings[$productId];

            if (!in_array('rating', $ignoredFields, true)) {
                $product['rating'] = $this->convertRatingSum($summary);
            }

            if (!in_array('rating_count', $ignoredFields, true)) {
                $product['rating_count'] = (int)$summary->getReviewsCount();
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
        array                      $productIds,
        FeedSpecificationInterface $feedSpecification
    ): array
    {
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
     * @param array $productIds
     * @param array $ratingsResult
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws NoSuchEntityException
     */
    private function getRatingSummaryWithParents(
        array                      $productIds,
        array                      $ratingsResult,
        FeedSpecificationInterface $feedSpecification
    ): array
    {

        $ratingSummary = $ratingsResult;
        $missingIds = array_diff($productIds, array_keys($ratingsResult));

        if (!$missingIds) {
            return $ratingSummary;
        }

        $childToParentMap = [];
        $parentIds = [];

        foreach ($missingIds as $childId) {
            $parent = $this->parentRelationsContext->getParentsByChildId($childId);
            if (!$parent) {
                continue;
            }

            $parentId = (int)$parent->getId();
            $childToParentMap[$childId] = $parentId;
            $parentIds[] = $parentId;
        }

        if (!$parentIds) {
            return $ratingSummary;
        }

        $parentIds = array_values(array_unique($parentIds));

        $parentRatings = $this->getRatings($parentIds, $feedSpecification);

        foreach ($childToParentMap as $childId => $parentId) {
            if (!empty($parentRatings[$parentId])) {
                $ratingSummary[$childId] = $parentRatings[$parentId];
            }
        }

        return $ratingSummary;
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
