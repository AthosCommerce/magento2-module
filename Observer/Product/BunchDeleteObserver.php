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

namespace AthosCommerce\Feed\Observer\Product;

use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Observer\BaseProductObserver;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class BunchDeleteObserver implements ObserverInterface
{
    /**
     * @var BaseProductObserver
     */
    private $baseProductObserver;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param BaseProductObserver $baseProductObserver
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        BaseProductObserver $baseProductObserver,
        AthosCommerceLogger $logger
    ) {
        $this->baseProductObserver = $baseProductObserver;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            $productIds = (array)$observer->getEvent()->getIdsToDelete();

            if (empty($productIds)) {
                $this->logger->debug('[BunchDeleteObserver] No product IDs to delete.');

                return;
            }

            $productIds = array_unique($productIds);
            // Process deletions in chunks to avoid memory issues,
            // Magento deletes products globally so marking all product ids for global delete.
            foreach (array_chunk($productIds, 1000) as $chunk) {
                $this->baseProductObserver->execute(
                    $chunk,
                    Actions::DELETE
                );
            }

            $this->logger->info(
                sprintf(
                    '[BunchDeleteObserver] Products (%d) marked for deletion.',
                    count($productIds)
                )
            );
        } catch (\Throwable $e) {
            $this->logger->critical(
                '[BunchDeleteObserver] Fatal error: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
