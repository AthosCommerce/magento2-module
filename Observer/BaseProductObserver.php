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

namespace AthosCommerce\Feed\Observer;

use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Service\Action\SetIndexingEntitiesToDeleteActionInterface;
use AthosCommerce\Feed\Service\Action\SetIndexingEntitiesToUpdateActionInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class BaseProductObserver
{
    /**
     * @var SetIndexingEntitiesToUpdateActionInterface
     */
    private $setIndexingEntitiesToUpdateAction;
    /**
     * @var SetIndexingEntitiesToDeleteActionInterface
     */
    private $setIndexingEntitiesToDeleteAction;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param SetIndexingEntitiesToUpdateActionInterface $setIndexingEntitiesToUpdateAction
     * @param SetIndexingEntitiesToDeleteActionInterface $setIndexingEntitiesToDeleteAction
     * @param \Magento\Framework\Logger\Monolog $logger
     */
    public function __construct(
        SetIndexingEntitiesToUpdateActionInterface $setIndexingEntitiesToUpdateAction,
        SetIndexingEntitiesToDeleteActionInterface $setIndexingEntitiesToDeleteAction,
        \Magento\Framework\Logger\Monolog                            $logger
    )
    {
        $this->setIndexingEntitiesToUpdateAction = $setIndexingEntitiesToUpdateAction;
        $this->setIndexingEntitiesToDeleteAction = $setIndexingEntitiesToDeleteAction;
        $this->logger = $logger;
    }

    /**
     * @param array $entityIds
     * @param string $action
     * @param bool $forceIndexable
     * @return void
     */
    public function execute(
        array  $entityIds,
        string $action,
        bool   $forceIndexable = false
    ): void
    {
        if (!$entityIds) {
            return;
        }
        switch ($action) {
            case Actions::UPSERT:
                $this->setIndexingEntitiesToUpdateAction->execute($entityIds, $forceIndexable);
                break;
            case Actions::DELETE:
                $this->setIndexingEntitiesToDeleteAction->execute($entityIds);
                break;
            default:
                $this->logger->error(
                    sprintf('Invalid action found: (%s)', $action)
                );
                break;
        }
        return;
    }
}
