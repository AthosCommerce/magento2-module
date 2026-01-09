<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Config;

use AthosCommerce\Feed\Api\Data\StoreConfigInterface;

class StoreConfigApiMapper
{
    /**
     * @param StoreConfigInterface $store
     * @return array
     */
    public function map(StoreConfigInterface $store): array
    {
        return [
            'storeId' => $store->getStoreId(),
            'storeCode' => $store->getStoreCode(),
            'siteId' => $store->getSiteId(),
            'secretKey' => $store->getSecretKey(),
            'endPoint' => $store->getEndPoint(),
            'enableLiveIndexing' => $store->getEnableLiveIndexing(),
            'entitySyncCronExpr' => $store->getEntitySyncCronExpr(),
            'perMinute' => $store->getPerMinute(),
            'chunkSize' => $store->getChunkSize(),
        ];
    }
}
