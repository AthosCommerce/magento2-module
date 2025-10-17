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

namespace AthosCommerce\Feed\Model\Feed\Collection;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogRule\Model\ResourceModel\Product\CollectionProcessor;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\PricesProvider;

class PricesModifier implements ModifierInterface
{
    /**
     * @var CollectionProcessor
     */
    private $collectionProcessor;

    /**
     * PricesModifier constructor.
     * @param CollectionProcessor $collectionProcessor
     */
    public function __construct(
        CollectionProcessor $collectionProcessor
    ) {
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @param Collection $collection
     * @param FeedSpecificationInterface $feedSpecification
     * @return Collection
     */
    public function modify(Collection $collection, FeedSpecificationInterface $feedSpecification): Collection
    {
        $ignoredFields = $feedSpecification->getIgnoreFields();
        if (!in_array(PricesProvider::FINAL_PRICE_KEY, $ignoredFields)
            || !in_array(PricesProvider::MAX_PRICE_KEY, $ignoredFields)
            || !in_array(PricesProvider::REGULAR_PRICE_KEY, $ignoredFields)
        ) {
            $collection->addPriceData();
            $this->collectionProcessor->addPriceData($collection);
        }

        return $collection;
    }
}
