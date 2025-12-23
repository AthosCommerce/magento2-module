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

use AthosCommerce\Feed\Api\LiveIndexing\DeleteEntityHandlerInterface;
use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Service\Api\ApiClient;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class DeleteEntityHandler implements DeleteEntityHandlerInterface
{
    /**
     * @var ApiClient
     */
    private $client;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param ApiClient $client
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        ApiClient       $client,
        AthosCommerceLogger $logger
    )
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function process(int $id): bool
    {
        try {
            return $this->client->send(
                ['entity_id' => $id],
                Constants::API_SCOPE_DELETE
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf("[DeleteEntity] Failed for entityId: (%s)", $id),
                [
                    'error' => $e->getMessage(),
                ]
            );

            return false;
        }
    }
}
