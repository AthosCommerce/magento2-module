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
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Emulation
     */
    private $emulation;
    /**
     * @var Store|null
     */
    private $currentStore = null;

    /**
     * StoreContextManager constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param Emulation $emulation
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Emulation $emulation
    ) {
        $this->storeManager = $storeManager;
        $this->emulation = $emulation;
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @throws NoSuchEntityException
     */
    public function setContextFromSpecification(FeedSpecificationInterface $feedSpecification): void
    {
        $storeCode = $feedSpecification->getStoreCode();
        if (!$storeCode) {
            return;
        }

        $store = $this->storeManager->getStore($storeCode);
        $this->currentStore = $store;
        $this->emulation->startEnvironmentEmulation(
            (int)$store->getId(),
            Area::AREA_FRONTEND,
            true
        );
    }

    /**
     * Get currently active store for the running context.
     *
     * @return Store|null
     */
    public function getStoreFromContext(): ?Store
    {
        if (null === $this->currentStore) {
            return $this->storeManager->getStore();
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
        $this->emulation->stopEnvironmentEmulation();
        $this->currentStore = null;
    }
}
