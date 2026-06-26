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

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Category;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\Context\StoreContextManager;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;

class CollectionBuilder
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var StoreContextManager
     */
    private $storeContextManager;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param CollectionFactory $collectionFactory
     * @param StoreContextManager $storeContextManager
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        CollectionFactory   $collectionFactory,
        StoreContextManager $storeContextManager,
        AthosCommerceLogger $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeContextManager = $storeContextManager;
        $this->logger = $logger;
    }

    /**
     * @param array $categoryIds
     * @param FeedSpecificationInterface $feedSpecification
     * @return Collection
     * @throws LocalizedException
     */
    public function buildCollection(
        array                      $categoryIds,
        FeedSpecificationInterface $feedSpecification
    ): Collection {
        $collection = $this->collectionFactory->create();
        $collection->setStore($feedSpecification->getStoreCode());
        $store = $this->storeContextManager->getStoreFromContext();
        $collection->setStoreId($store);
        $rootId = $store->getRootCategoryId();

        $selectAttributes = [
            CategoryInterface::KEY_NAME,
            CategoryInterface::KEY_IS_ACTIVE,
            CategoryInterface::KEY_PATH
        ];
        if ($feedSpecification->getIncludeMenuCategories()) {
            $selectAttributes[] = CategoryInterface::KEY_INCLUDE_IN_MENU;
        }

        if ($feedSpecification->getIncludeUrlHierarchy()) {
            $collection->addUrlRewriteToResult();
        }

        $collection->addAttributeToSelect($selectAttributes);
        $collection->addAttributeToFilter(CategoryInterface::KEY_IS_ACTIVE, 1)
            ->addAttributeToFilter('entity_id', ['in' => $categoryIds])
            ->addAttributeToFilter(
                [
                    ['attribute' => 'path', 'like' => "1/{$rootId}/%"],
                    ['attribute' => 'entity_id', 'eq' => $rootId]
                ]
            );

        $this->logger->debug(
            'CategoryCollectionBuilder',
            [
                'query' => $collection->getSelect()->__toString(),
                'method' => __METHOD__
            ]
        );
        return $collection;
    }
}
