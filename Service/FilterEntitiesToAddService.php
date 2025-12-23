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

namespace AthosCommerce\Feed\Service;

use AthosCommerce\Feed\Service\FilterEntitiesToAddServiceInterface;
use AthosCommerce\Feed\Service\Provider\Api\IndexingEntityProviderInterface;
use AthosCommerce\Feed\Api\Data\IndexingEntityInterface;
use AthosCommerce\Feed\Model\Api\MagentoEntityInterface;

class FilterEntitiesToAddService implements FilterEntitiesToAddServiceInterface
{
    /**
     * @var IndexingEntityProviderInterface
     */
    private $indexingEntityProvider;

    /**
     * @param IndexingEntityProviderInterface $indexingEntityProvider
     */
    public function __construct(
        IndexingEntityProviderInterface $indexingEntityProvider
    )
    {
        $this->indexingEntityProvider = $this->indexingEntityProvider;
    }

    /**
     * @param MagentoEntityInterface[] $magentoEntities
     * @param string $type
     * @param string $siteId
     * @param string[] $entitySubtypes
     *
     * @return array
     */
    public function execute(
        array  $magentoEntities,
        string $type,
        string $siteId,
        array  $entitySubtypes = []
    ): \Generator
    {
        $entityIds = array_map(
            function (MagentoEntityInterface $magentoEntity) {
                return (int)$magentoEntity->getEntityId();
            },
            $magentoEntities
        );

        $athosEntityIds = $this->getAthosEntityIds(
            $type,
            $siteId,
            $entityIds,
            $entitySubtypes
        );

        unset($entityIds);

        foreach ($magentoEntities as $magentoEntity) {
            $entityKey =
                $magentoEntity->getEntityId()
                . '-' . ($magentoEntity->getEntityParentId() ?: 0)
                . '-' . $magentoEntity->getEntitySubtype();

            if (
                empty($athosEntityIds)
                || !in_array($entityKey, $athosEntityIds, true)
            ) {
                yield $magentoEntity;
            }
        }
    }

    /**
     * @param string $type
     * @param string $siteId
     * @param int[] $entityIds
     * @param string[] $entitySubtypes
     *
     * @return string[]
     */
    private function getAthosEntityIds(
        string $type,
        string $siteId,
        array  $entityIds,
        array  $entitySubtypes
    ): array
    {
        $athosEntities = $this->indexingEntityProvider->get(
            $type,
            [$siteId],
            $entityIds,
            $entitySubtypes,
        );
        $return = array_map(
            static fn(IndexingEntityInterface $indexingEntity) => (
                $indexingEntity->getTargetId()
                . '-' . ($indexingEntity->getTargetParentId() ?? 0)
                . '-' . $indexingEntity->getTargetEntitySubtype()
            ),
            $athosEntities,
        );
        foreach ($athosEntities as $athosEntity) {
            if (method_exists($athosEntity, 'clearInstance')) {
                $athosEntity->clearInstance();
            }
        }
        unset($athosEntities);

        return $return;
    }
}
