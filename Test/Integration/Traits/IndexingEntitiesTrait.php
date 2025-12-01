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

namespace AthosCommerce\Feed\Test\Integration\Traits;

use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @property ObjectManagerInterface $objectManager
 */
trait IndexingEntitiesTrait
{
    /**
     * @param string $siteId
     *
     * @return void
     */
    private function cleanIndexingEntities(string $siteId): void
    {
        $searchCriteriaBuilderFactory = $this->objectManager->get(SearchCriteriaBuilderFactory::class);
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter(
            IndexingEntity::SITE_ID,
             $siteId,
             'like',
        );
        $searchCriteria = $searchCriteriaBuilder->create();
        /** @var IndexingEntityRepositoryInterface $repository */
        $repository = $this->objectManager->get(IndexingEntityRepositoryInterface::class);
        $indexingEntitiesToDelete = $repository->getList($searchCriteria);
        foreach ($indexingEntitiesToDelete->getItems() as $indexingEntity) {
            try {
                $repository->delete($indexingEntity);
            } catch (LocalizedException) {
                // this is fine, indexingEntity already deleted
            }
        }
    }
}
