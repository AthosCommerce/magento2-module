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

namespace AthosCommerce\Feed\Model\Feed\Context;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;

class StoreContextManager implements ContextManagerInterface
{
    /**
     * @var Store|null
     */
    private $currentStore = null;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Emulation
     */
    private $emulation;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * StoreContextManager constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param Emulation $emulation
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Emulation             $emulation,
        AthosCommerceLogger   $logger
    )
    {
        $this->storeManager = $storeManager;
        $this->emulation = $emulation;
        $this->logger = $logger;
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     * @return void
     */
    public function setContextFromSpecification(FeedSpecificationInterface $feedSpecification): void
    {
        $storeCode = $feedSpecification->getStoreCode();
        if (!$storeCode) {
            $feedSpecificationData = method_exists($feedSpecification, '__toArray')
                ? $feedSpecification->__toArray()
                : null;
            if (is_array($feedSpecificationData)) {
                $feedSpecificationData = \AthosCommerce\Feed\Helper\SensitiveDataMasker::mask(
                    $feedSpecificationData
                );
            }
            $this->logger->error(
                'StoreCode not found',
                [
                    'feedSpecification' => $feedSpecificationData
                ]
            );
            return;
        }

        try {
            $store = $this->storeManager->getStore($storeCode);

            $this->emulation->startEnvironmentEmulation(
                (int)$store->getId(),
                Area::AREA_FRONTEND,
                true
            );

            $this->currentStore = $store;
        } catch (\Throwable $exception) {
            $this->currentStore = null;

            try {
                $this->emulation->stopEnvironmentEmulation();
            } catch (\Throwable $stopException) {
                // ignore
            }

            $feedSpecificationData = method_exists($feedSpecification, '__toArray')
                ? $feedSpecification->__toArray()
                : null;
            if (is_array($feedSpecificationData)) {
                $feedSpecificationData = \AthosCommerce\Feed\Helper\SensitiveDataMasker::mask(
                    $feedSpecificationData
                );
            }
            $this->logger->critical(
                $exception->getMessage(),
                [
                    'trace' => $exception->getTraceAsString(),
                    'feedSpecification' => $feedSpecificationData
                ]
            );
        }
    }

    /**
     * Get currently active store for the running context.
     *
     * @return Store|null
     */
    public function getStoreFromContext(): ?Store
    {
        if (null === $this->currentStore) {
            try {
                return $this->storeManager->getStore();
            } catch (NoSuchEntityException $exception) {
                $this->logger->error($exception->getMessage());
                return null;
            }
        }

        return $this->currentStore;
    }

    /**
     * Resetting the environment and current store reference
     *
     * @return void
     */
    public function resetContext(): void
    {
        try {
            $this->emulation->stopEnvironmentEmulation();
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }
        $this->currentStore = null;
    }
}
