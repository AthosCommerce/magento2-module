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

namespace AthosCommerce\Feed\Model\LiveIndexing;

use AthosCommerce\Feed\Api\RetryManagerInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class RetryManager implements RetryManagerInterface
{
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        AthosCommerceLogger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @param int $entityId
     * @param string $action
     * @param string|null $errorMessage
     *
     * @return void
     */
    public function markForRetry(
        int $entityId,
        string $action,
        ?string $errorMessage = null
    ): void {
        $this->logger->info(
            "Marking for retry",
            [
                'entityId' => $entityId,
                'action' => $action,
                'message' => $errorMessage,
            ]
        );
        // TODO: Implement markForRetry() method.
    }

    /**
     * @param int $entityId
     * @param string $action
     *
     * @return void
     */
    public function resetRetry(int $entityId, string $action): void
    {
        $this->logger->info(
            "ResetRetry",
            [
                'entityId' => $entityId,
                'action' => $action,
            ]
        );
        // TODO: Implement resetRetry() method.
    }
}
