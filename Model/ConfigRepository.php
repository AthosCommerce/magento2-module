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

namespace AthosCommerce\Feed\Model;

use AthosCommerce\Feed\Model\Config\ConfigMap;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Model\AbstractModel;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class ConfigRepository
{
    /** @var ResourceConnection */
    private $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource
    )
    {
        $this->resource = $resource;
    }

    /**
     * Fetch configuration rows for store scopes
     *
     * @return array
     */
    public function fetchStoreConfigRows(): array
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('core_config_data');

        return $connection->fetchAll(
            $connection->select()
                ->from($table, ['scope_id', 'path', 'value'])
                ->where('scope = ?', 'stores')
                ->where('path IN (?)', array_keys(\AthosCommerce\Feed\Model\Config\ConfigMap::getPathToKeyMap()))
                ->order('scope_id ASC')
        );
    }
}
