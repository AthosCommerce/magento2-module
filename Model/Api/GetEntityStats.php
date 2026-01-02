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

use AthosCommerce\Feed\Api\Data\ProductCountItemInterface;
use AthosCommerce\Feed\Api\Data\ProductCountItemInterfaceFactory;
use AthosCommerce\Feed\Api\GetEntityStatsInterface;
use AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface;
use AthosCommerce\Feed\Api\Data\ProductCountListResponseInterface;
use AthosCommerce\Feed\Api\Data\ProductCountListResponseInterfaceFactory;
use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Model\Source\Actions;

class GetEntityStats implements GetEntityStatsInterface
{
    /**
     * @var IndexingEntityRepositoryInterface
     */
    private $entityRepository;
    /**
     * @var ProductCountListResponseInterfaceFactory
     */
    private $responseFactory;
    /**
     * @var ProductCountItemInterface
     */
    private $itemFactory;

    /**
     * @param IndexingEntityRepositoryInterface $entityRepository
     * @param ProductCountListResponseInterfaceFactory $responseFactory
     * @param ProductCountItemInterfaceFactory $itemFactory
     */
    public function __construct(
        IndexingEntityRepositoryInterface    $entityRepository,
        ProductCountListResponseInterfaceFactory $responseFactory,
        ProductCountItemInterfaceFactory     $itemFactory
    )
    {
        $this->entityRepository = $entityRepository;
        $this->responseFactory = $responseFactory;
        $this->itemFactory = $itemFactory;
    }

    /**
     * @param string|null $siteId
     * @return \AthosCommerce\Feed\Api\Data\ProductCountListResponseInterface
     */
    public function get(?string $siteId = null): \AthosCommerce\Feed\Api\Data\ProductCountListResponseInterface
    {
        $items = [];

        $siteIds = $siteId
            ? [$siteId]
            : $this->entityRepository->getAllSiteIds();


        foreach ($siteIds as $sid) {
            /** @var \AthosCommerce\Feed\Api\Data\ProductCountItemInterface $item */
            $item = $this->itemFactory->create();

            $item->setSiteId($sid);
            $item->setTotalProductCount(
                $this->entityRepository->count(
                    Constants::PRODUCT_KEY,
                    $sid
                )
            );
            $item->setDeleteProductCount(
                $this->entityRepository->count(
                    Constants::PRODUCT_KEY,
                    $sid,
                    Actions::DELETE
                )
            );
            $item->setUpsertProductCount(
                $this->entityRepository->count(
                    Constants::PRODUCT_KEY,
                    $sid,
                    Actions::UPSERT
                )
            );

            $items[] = $item;
        }

        /** @var \AthosCommerce\Feed\Api\Data\ProductCountListResponseInterface $response */
        $response = $this->responseFactory->create();
        $response->setItems($items);
        return $response;
    }
}
