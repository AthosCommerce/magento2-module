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
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use AthosCommerce\Feed\Observer\BaseProductObserver;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class BunchDeleteObserver implements ObserverInterface
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
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param BaseProductObserver $baseProductObserver
     * @param LoggerInterface $logger
     * @param ProductRepositoryInterface $productRepository
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        BaseProductObserver        $baseProductObserver,
        LoggerInterface            $logger,
        ProductRepositoryInterface $productRepository,
        ScopeConfigInterface       $scopeConfig
    )
    {
        $this->baseProductObserver = $baseProductObserver;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
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
            $productIdsToDelete = (array)$event->getIdsToDelete();

            foreach ($productIdsToDelete as $productId) {
                try {
                    $product = $this->productRepository->getById($productId);

                    /** @var array $storeIds */
                    $storeIds = $product->getStoreIds();

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

                            $this->baseProductObserver->execute([$productId], Actions::DELETE);

                            $this->logger->debug(
                                'BunchDeleteObserver executed',
                                [
                                    'productId' => $productId,
                                    'storeId' => $storeId
                                ]
                            );
                        } catch (\Throwable $storeEx) {
                            // Handle exceptions per store so loop continues
                            $this->logger->error(
                                "Error processing product ID {$productId} for store ID {$storeId}: " . $storeEx->getMessage(),
                                ['trace' => $storeEx->getTraceAsString()]
                            );
                        }
                    }
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    $this->logger->warning("Product not found: ID {$productId}");
                } catch (\Throwable $productEx) {
                    $this->logger->error(
                        "Error processing product ID {$productId}: " . $productEx->getMessage(),
                        ['trace' => $productEx->getTraceAsString()]
                    );
                }
            }
        } catch (\Throwable $e) {
            $this->logger->critical(
                'BunchDeleteObserver general error: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]
            );
        }

    }
}
