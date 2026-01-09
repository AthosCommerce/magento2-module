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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use AthosCommerce\Feed\Observer\BaseProductObserver;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class DeleteObserver implements ObserverInterface
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
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param BaseProductObserver $baseProductObserver
     * @param AthosCommerceLogger $logger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        BaseProductObserver $baseProductObserver,
        AthosCommerceLogger $logger,
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->baseProductObserver = $baseProductObserver;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $event = $observer->getEvent();
            $product = $event->getProduct();
            if (!$product || !$product->getId()) {
                return;
            }

            $storeIds = method_exists($product, 'getStoreIds')
                ? $product->getStoreIds()
                : [];
            foreach ($storeIds as $storeId) {
                try {
                    $liveIndexing = (bool)$this->scopeConfig->getValue(
                        Constants::XML_PATH_LIVE_INDEXING_ENABLED,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        $storeId
                    );

                    if (!$liveIndexing) {
                        continue;
                    }

                    $nextAction = ($product->getStatus() != 1 || $product->getVisibility() == 1)
                        ? Actions::DELETE
                        : Actions::UPSERT;

                    $this->baseProductObserver->execute([$product->getId()], $nextAction);

                    $this->logger->debug(
                        '[DeleteObserver] Product marked for deletion: ',
                        [
                            'ids' => $product->getId(),
                            'store_id' => $storeId,
                            'action' => $nextAction,
                        ]
                    );
                } catch (\Throwable $storeEx) {
                    // Handle exceptions per store so the loop continues
                    $this->logger->error(
                        '[DeleteObserver] Exception thrown for store',
                        [
                            'product_id' => $product->getId(),
                            'store_id' => $storeId,
                            'message' => $storeEx->getMessage(),
                            'trace' => $storeEx->getTraceAsString(),
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            // Handle general observer exceptions
            $this->logger->error(
                '[DeleteObserver] Exception thrown: ',
                [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
        }
    }
}
