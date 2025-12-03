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

use AthosCommerce\Feed\Model\Source\Actions;
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
     * @param BaseProductObserver $baseProductObserver
     * @param LoggerInterface $logger
     */
    public function __construct(
        BaseProductObserver $baseProductObserver,
        LoggerInterface $logger
    ) {
        $this->baseProductObserver = $baseProductObserver;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $product = $event->getProduct();
        if (!$product || !$product->getId()) {
            return;
        }
        $nextAction = ($product->getStatus() != 1 || $product->getVisibility() == 1)
            ? Actions::DELETE
            : Actions::UPSERT;

        $this->baseProductObserver->execute([$product->getId], $nextAction);

        $this->logger->debug(
            'UpdateObserver executed',
            [
                'ids' => $product->getId(),
            ]
        );
    }
}
