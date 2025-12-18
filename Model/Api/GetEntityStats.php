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

namespace AthosCommerce\Feed\Model\Api;

use AthosCommerce\Feed\Api\GetEntityStatsInterface;
use AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface;
use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Model\Source\Actions;

class GetEntityStats implements GetEntityStatsInterface
{

    /**
     * @var IndexingEntityRepositoryInterface
     */
    private $entityRepository;

    /**
     * @param IndexingEntityRepositoryInterface $entityRepository
     */
    public function __construct(
        IndexingEntityRepositoryInterface $entityRepository
    )
    {
        $this->entityRepository = $entityRepository;
    }

    /**
     * @param string $siteId
     * @return array
     */
    public function get(string $siteId): array
    {
        $totalProductCount = $this->entityRepository->count(
            Constants::PRODUCT_KEY,
            $siteId
        );
        $deleteProductCount = $this->entityRepository->count(
            Constants::PRODUCT_KEY,
            $siteId,
            Actions::DELETE
        );
        $upsertProductCount = $this->entityRepository->count(
            Constants::PRODUCT_KEY,
            $siteId,
            Actions::UPSERT
        );
        return [
            'totalProductCount' => $totalProductCount,
            'deleteProductCount' => $deleteProductCount,
            'upsertProductCount' => $upsertProductCount
        ];
    }
}
