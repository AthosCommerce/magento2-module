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

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;

class CollectionBuilder
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * CollectionBuilder constructor.
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param array $categoryIds
     * @param FeedSpecificationInterface $feedSpecification
     * @return Collection
     * @throws LocalizedException
     */
    public function buildCollection(
        array $categoryIds,
        FeedSpecificationInterface $feedSpecification
    ) : Collection {
        $collection = $this->collectionFactory->create();
        $collection->setStore($feedSpecification->getStoreCode());
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
            ->addAttributeToFilter('entity_id', ['in' => $categoryIds]);

        return $collection;
    }
}
