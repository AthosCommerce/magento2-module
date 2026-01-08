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

namespace AthosCommerce\Feed\Service\Action;

use Magento\Framework\App\ResourceConnection;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class DeleteIndexingEntitiesAction implements DeleteIndexingEntitiesActionInterface
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param ResourceConnection $resource
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        ResourceConnection  $resource,
        AthosCommerceLogger $logger
    )
    {
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * @param int $siteId
     * @param bool $isEnabled
     * @return bool
     */
    public function delete(int $siteId, bool $isEnabled): bool
    {
        if (!$siteId) {
            return true;
        }

        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('athoscommerce_indexing_entity');

        try {
            $updatedRows = $connection->update(
                $tableName,
                [
                    'is_indexable' => 0,
                    'next_action' => null
                ],
                [
                    'site_id = ?' => $siteId,
                ]
            );

            $this->logger->info(
                sprintf(
                    'Successfully updated Next action to Null for live indexing products. Soft-disabled %d rows for site_id=%s',
                    $updatedRows,
                    $siteId
                )
            );

            return true;
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf(
                    'Failed to soft-disable rows for site_id=%s. Error: %s',
                    $siteId,
                    $e->getMessage()
                ),
                ['exception' => $e]
            );

            return false;
        }
    }
}
