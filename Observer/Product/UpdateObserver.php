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
use Psr\Log\LoggerInterface;

class UpdateObserver implements ObserverInterface
{
    /**
     * @var BaseProductObserver
     */
    private $baseProductObserver;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param BaseProductObserver $baseProductObserver
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        BaseProductObserver  $baseProductObserver,
        LoggerInterface      $logger,
        ScopeConfigInterface $scopeConfig,
    )
    {
        $this->baseProductObserver = $baseProductObserver;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            $event = $observer->getEvent();
            $product = $event->getProduct();
            $storeIds = method_exists($product, 'getStoreIds') ? $product->getStoreIds() : [];

            if (!$product || !$product->getId()) {
                return;
            }

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

                    $this->baseProductObserver->execute(
                        [$product->getId()],
                        $nextAction,
                        true
                    );

                    $this->logger->debug(
                        '[UpdateObserver] executed',
                        [
                            'id(s)' => $product->getId(),
                            'store_id' => $storeId,
                            'nextAction' => $nextAction,
                        ]
                    );
                } catch (\Throwable $storeEx) {
                    $this->logger->error(
                        '[UpdateObserver] error for store',
                        [
                            'product_id' => $product->getId(),
                            'store_id' => $storeId,
                            'message' => $storeEx->getMessage(),
                            'trace' => $storeEx->getTraceAsString()
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                '[UpdateObserver] error: ' . $e->getMessage(),
                [
                    'product_id' => $product->getId() ?? null,
                    'store_ids' => $storeIds ?? [],
                    'trace' => $e->getTraceAsString()
                ]
            );
        }
    }
}
