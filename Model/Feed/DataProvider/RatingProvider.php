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

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Review\Model\ResourceModel\Review\Summary\Collection;
use Magento\Review\Model\Review;
use Magento\Review\Model\Review\Summary;
use Magento\Review\Model\ResourceModel\Review\Summary\CollectionFactory as SummaryCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var ParentVariantResolver
     */
    private $parentVariantResolver;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * Runtime cache
     *
     * @var array<int, Summary>
     */
    private $ratingsCache = [];

    /**
     * @param SummaryCollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     * @param ParentVariantResolver $parentVariantResolver
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        SummaryCollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        ParentVariantResolver $parentVariantResolver,
        AthosCommerceLogger $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->parentVariantResolver = $parentVariantResolver;
        $this->logger = $logger;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(
        array $products,
        FeedSpecificationInterface $feedSpecification
    ): array {
        $ignoredFields = $feedSpecification->getIgnoreFields();

        if (
            in_array('rating', $ignoredFields, true)
            && in_array('rating_count', $ignoredFields, true)
        ) {
            return $products;
        }

        $productIds = [];
        foreach ($products as $product) {
            $productId = isset($product['entity_id']) ? (int)$product['entity_id'] : 0;
            if ($productId > 0) {
                $productIds[] = $productId;
            }
        }

        $productIds = array_values(array_unique($productIds));
        $this->ratingsCache = $this->getRatings($productIds, $feedSpecification);

        foreach ($products as &$product) {
            /** @var Product|null $productModel */
            $productModel = isset($product['product_model']) ? $product['product_model'] : null;
            $productId = isset($product['entity_id']) ? (int)$product['entity_id'] : 0;

            if ($productId <= 0) {
                continue;
            }

            $summary = $this->resolveSummaryForRow($product, $productModel, $productId, $feedSpecification);

            if (!$summary instanceof Summary) {
                continue;
            }

            if (!in_array('rating', $ignoredFields, true)) {
                $product['rating'] = $this->convertRatingSum($summary);
            }

            if (!in_array('rating_count', $ignoredFields, true)) {
                $product['rating_count'] = (int)$summary->getReviewsCount();
            }
        }
        unset($product);

        return $products;
    }

    /**
     * @param Summary $summary
     * @return float
     */
    private function convertRatingSum(Summary $summary): float
    {
        return 5 * ((int)$summary->getRatingSummary() / 100);
    }

    /**
     * @param array $productIds
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws NoSuchEntityException
     */
    private function getRatings(
        array $productIds,
        FeedSpecificationInterface $feedSpecification
    ): array {
        if (empty($productIds)) {
            return [];
        }

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
            $result[(int)$item->getEntityPkValue()] = $item;
        }

        return $result;
    }

    /**
     * Resolve rating summary for the current row.
     *
     * Priority:
     * 1. current product rating
     * 2. correctly resolved parent product rating for the current row
     *
     * @param array $row
     * @param Product|null $productModel
     * @param int $productId
     * @param FeedSpecificationInterface $feedSpecification
     * @return Summary|null
     * @throws NoSuchEntityException
     */
    private function resolveSummaryForRow(
        array $row,
        ?Product $productModel,
        int $productId,
        FeedSpecificationInterface $feedSpecification
    ): ?Summary {
        if (isset($this->ratingsCache[$productId]) && $this->ratingsCache[$productId] instanceof Summary) {
            return $this->ratingsCache[$productId];
        }

        if (!$productModel instanceof Product) {
            return null;
        }

        $parentProduct = $this->parentVariantResolver->resolveParentProductForRow($row, $productModel);
        if (!$parentProduct instanceof Product) {
            return null;
        }

        $parentId = (int)$parentProduct->getId();

        if (!isset($this->ratingsCache[$parentId])) {
            $parentRatings = $this->getRatings([$parentId], $feedSpecification);
            if (isset($parentRatings[$parentId]) && $parentRatings[$parentId] instanceof Summary) {
                $this->ratingsCache[$parentId] = $parentRatings[$parentId];
            }
        }

        if (isset($this->ratingsCache[$parentId]) && $this->ratingsCache[$parentId] instanceof Summary) {
            $this->logger->debug(
                'RatingProvider: Resolved parent rating for child row',
                [
                    'childId' => $productId,
                    'parentId' => $parentId,
                ]
            );

            return $this->ratingsCache[$parentId];
        }

        return null;
    }

    public function reset(): void
    {
        $this->ratingsCache = [];
    }

    public function resetAfterFetchItems(): void
    {
        // do nothing
    }
}
