<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\Collection;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Status;

class StockModifier implements ModifierInterface
{
    private const STOCK_STATUS_FILTER_FLAG = 'has_stock_status_filter';

    /**
     * @var Status
     */
    private $status;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param Status $status
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        Status              $status,
        AthosCommerceLogger $logger
    )
    {
        $this->status = $status;
        $this->logger = $logger;
    }

    /**
     * Modify collection to add stock filter/data
     *
     * @param Collection $collection
     * @param FeedSpecificationInterface $feedSpecification
     * @return Collection
     */
    public function modify(Collection $collection, FeedSpecificationInterface $feedSpecification): Collection
    {
        if ($collection->hasFlag(self::STOCK_STATUS_FILTER_FLAG)) {
            return $collection;
        }

        try {
            $includeOutOfStock = (bool)$feedSpecification->getIncludeOutOfStock();

            $this->status->addStockDataToCollection(
                $collection,
                !$includeOutOfStock
            );

            $collection->setFlag(self::STOCK_STATUS_FILTER_FLAG, true);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->warning(
                'Skipped stock status join: No linked MSI stock found for the current store scope.',
                [
                    'exception' => $e->getMessage()
                ]
            );
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Failed to apply stock modifier to collection',
                [
                    'exception' => $exception->getMessage()
                ]
            );
        }

        return $collection;
    }
}
