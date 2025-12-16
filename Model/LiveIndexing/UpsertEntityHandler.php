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

use AthosCommerce\Feed\Api\LiveIndexing\UpsertEntityHandlerInterface;
use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Service\Api\ApiClient;
use Psr\Log\LoggerInterface;

class UpsertEntityHandler implements UpsertEntityHandlerInterface
{
    /**
     * @var ApiClient
     */
    private $client;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ApiClient $client
     * @param LoggerInterface $logger
     */
    public function __construct(
        ApiClient       $client,
        LoggerInterface $logger
    )
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * @param array $payload
     *
     * @return bool
     */
    public function process(array $payload): bool
    {
        $entityId = 0;
        if (!empty($payload['entity_id'])) {
            $entityId = (int)$payload['entity_id'];
        }
        try {
            return $this->client->send(
                $payload,
                Constants::API_SCOPE_UPSERT
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf(
                    "[UpsertEntity] Failed for entityId: (%s)", $entityId),
                [
                    'payload' => $payload,
                    'error' => $e->getMessage(),
                ]
            );

            return false;
        }
    }
}
