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

namespace AthosCommerce\Feed\Api;

use AthosCommerce\Feed\Api\Data\IndexingEntityInterface;
use AthosCommerce\Feed\Api\Data\IndexingEntitySearchResultsInterface;
use AthosCommerce\Feed\Model\Source\Actions;
use Magento\Framework\Api\SearchCriteriaInterface;

interface IndexingEntityRepositoryInterface
{
    /**
     * @param int $indexingEntityId
     *
     * @return \AthosCommerce\Feed\Api\Data\IndexingEntityInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $indexingEntityId): IndexingEntityInterface;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @param bool $collectionSizeRequired
     *
     * @return \AthosCommerce\Feed\Api\Data\IndexingEntitySearchResultsInterface
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria,
        bool $collectionSizeRequired = false,
    ): IndexingEntitySearchResultsInterface;

    /**
     * @return \AthosCommerce\Feed\Api\Data\IndexingEntityInterface
     */
    public function create(): IndexingEntityInterface;

    /**
     * @param \AthosCommerce\Feed\Api\Data\IndexingEntityInterface $indexingEntity
     *
     * @return \AthosCommerce\Feed\Api\Data\IndexingEntityInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function save(IndexingEntityInterface $indexingEntity): IndexingEntityInterface;

    //phpcs:disable Security.BadFunctions.FilesystemFunctions.WarnFilesystem
    /**
     * @param \AthosCommerce\Feed\Api\Data\IndexingEntityInterface $indexingEntity
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(IndexingEntityInterface $indexingEntity): void;
    //phpcs:enable Security.BadFunctions.FilesystemFunctions.WarnFilesystem

    /**
     * @param int $indexingEntityId
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteById(int $indexingEntityId): void;

    /**
     * @param string|null $entityType
     * @param string|null $siteId
     * @param Actions|null $nextAction
     * @param bool|null $isIndexable
     *
     * @return int
     */
    public function count(
        ?string $entityType = null,
        ?string $siteId = null,
        ?Actions $nextAction = null,
        ?bool $isIndexable = null,
    ): int;

    /**
     * @param string|null $siteId
     *
     * @return string[]
     */
    public function getUniqueEntityTypes(?string $siteId = null): array;
}
