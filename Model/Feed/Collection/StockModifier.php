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
use Magento\CatalogInventory\Model\ResourceModel\Stock\Status;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;

class StockModifier implements ModifierInterface
{
    /**
     * @var Status
     */
    private $status;

    /**
     * StockModifier constructor.
     * @param Status $status
     */
    public function __construct(
        Status $status
    ) {
        $this->status = $status;
    }

    /**
     * @param Collection $collection
     * @param FeedSpecificationInterface $feedSpecification
     * @return Collection
     */
    public function modify(Collection $collection, FeedSpecificationInterface $feedSpecification): Collection
    {
        $includeOutOfStock = $feedSpecification->getIncludeOutOfStock();
        $stockFlag = 'has_stock_status_filter';
        if (!$collection->hasFlag($stockFlag)) {
            $this->status->addStockDataToCollection(
                $collection,
                !$includeOutOfStock
            );
            $collection->setFlag($stockFlag, true);
        }

        return $collection;
    }
}
