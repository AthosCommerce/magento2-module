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

use AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface;
use AthosCommerce\Feed\Exception\CouldNotDeleteException;
use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Model\ResourceModel\IndexingEntity\CollectionFactory;
use AthosCommerce\Feed\Model\IndexingEntitySearchResultsFactory;
use AthosCommerce\Feed\Model\IndexingEntitySearchResults;
use AthosCommerce\Feed\Model\ResourceModel\IndexingEntity as IndexingEntityResourceModel;
use AthosCommerce\Feed\Api\Data\IndexingEntityInterface;
use AthosCommerce\Feed\Api\Data\IndexingEntityInterfaceFactory;
use AthosCommerce\Feed\Api\Data\IndexingEntitySearchResultsInterface;
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Validator\ValidatorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Psr\Log\LoggerInterface;

class IndexingEntityRepository implements IndexingEntityRepositoryInterface
{
    /**
     * @var IndexingEntityInterfaceFactory
     */
    private $indexingEntityFactory;
    /**
     * @var IndexingEntityResourceModel
     */
    private $indexingEntityResourceModel;
    /**
     * @var IndexingEntitySearchResultsFactory
     */
    private $searchResultsFactory;
    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;
    /**
     * @var CollectionFactory
     */
    private $indexingEntityCollectionFactory;
    /**
     * @var ValidatorInterface
     */
    private $indexingEntityValidator;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param IndexingEntityInterfaceFactory $indexingEntityFactory
     * @param IndexingEntityResourceModel $indexingEntityResourceModel
     * @param IndexingEntitySearchResultsFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param CollectionFactory $indexingEntityCollectionFactory
     * @param ValidatorInterface $indexingEntityValidator
     * @param LoggerInterface $logger
     */
    public function __construct(
        IndexingEntityInterfaceFactory $indexingEntityFactory,
        IndexingEntityResourceModel $indexingEntityResourceModel,
        IndexingEntitySearchResultsFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        CollectionFactory $indexingEntityCollectionFactory,
        ValidatorInterface $indexingEntityValidator,
        LoggerInterface $logger,
    ) {
        $this->indexingEntityFactory = $indexingEntityFactory;
        $this->indexingEntityResourceModel = $indexingEntityResourceModel;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->indexingEntityCollectionFactory = $indexingEntityCollectionFactory;
        $this->indexingEntityValidator = $indexingEntityValidator;
        $this->logger = $logger;
    }

    /**
     * @return IndexingEntityInterface
     */
    public function create(): IndexingEntityInterface
    {
        return $this->indexingEntityFactory->create();
    }

    /**
     * @param int $indexingEntityId
     *
     * @return IndexingEntityInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $indexingEntityId): IndexingEntityInterface
    {
        /** @var AbstractModel|IndexingEntityInterface $indexingEntity */
        $indexingEntity = $this->create();
        $this->indexingEntityResourceModel->load(
            $indexingEntity,
            $indexingEntityId,
            IndexingEntityResourceModel::ID_FIELD_NAME,
        );
        if (!$indexingEntity->getId()) {
            throw NoSuchEntityException::singleField(
                IndexingEntityResourceModel::ID_FIELD_NAME,
                $indexingEntityId,
            );
        }

        return $indexingEntity;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @param bool $collectionSizeRequired
     *
     * @return IndexingEntitySearchResultsInterface
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria,
        bool $collectionSizeRequired = false,
    ): IndexingEntitySearchResultsInterface {
        /** @var IndexingEntitySearchResults $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        $collection = $this->indexingEntityCollectionFactory->create();

        $this->collectionProcessor->process(
            $searchCriteria,
            $collection,
        );
        $this->logger->debug(
            'Method: {method}, Indexing Entity getList Query: {query}',
            [
                'method' => __METHOD__,
                'line' => __LINE__,
                'query' => $collection->getSelect()->__toString(),
            ],
        );

        $searchResults->setItems(
            $collection->getItems(), //@phpstan-ignore-line
        );
        $count = $searchCriteria->getPageSize() && $collectionSizeRequired
            ? $collection->getSize()
            : count($collection);
        $searchResults->setTotalCount(count: $count);
        $collection->clear();
        unset($collection);

        return $searchResults;
    }

    /**
     * @param IndexingEntityInterface $indexingEntity
     *
     * @return IndexingEntityInterface
     * @throws CouldNotSaveException
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     */
    public function save(IndexingEntityInterface $indexingEntity): IndexingEntityInterface
    {
        if (!$this->indexingEntityValidator->isValid($indexingEntity)) {
            $messages = $this->indexingEntityValidator->hasMessages()
                ? $this->indexingEntityValidator->getMessages()
                : [];
            throw new CouldNotSaveException(
                __(
                    'Could not save Indexing Entity: %1',
                    implode('; ', $messages),
                ),
            );
        }

        try {
            /** @var AbstractModel $indexingEntity */
            $this->indexingEntityResourceModel->save($indexingEntity);
        } catch (AlreadyExistsException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save Indexing Entity: %1', $exception->getMessage()),
                $exception,
                $exception->getCode(),
            );
        }

        return $this->getById(
            (int)$indexingEntity->getId(),
        );
    }

    //phpcs:disable Security.BadFunctions.FilesystemFunctions.WarnFilesystem

    /**
     * @param IndexingEntityInterface $indexingEntity
     *
     * @return void
     * @throws LocalizedException
     */
    public function delete(IndexingEntityInterface $indexingEntity): void
    {
        //phpcs:enable Security.BadFunctions.FilesystemFunctions.WarnFilesystem
        try {
            /** @var AbstractModel $indexingEntity */
            $this->indexingEntityResourceModel->delete($indexingEntity);
        } catch (LocalizedException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $message = __('Could not delete Indexing Entity: %1', $exception->getMessage());
            $this->logger->error(
                (string)$message,
                [
                    'exception' => $exception::class,
                    'method' => __METHOD__,
                    'indexingEntity' => [
                        'entityId' => $indexingEntity->getId(),
                        'targetId' => $indexingEntity->getTargetId(),
                        'targetParentId' => $indexingEntity->getTargetParentId(),
                        'targetEntityType' => $indexingEntity->getTargetEntityType(),
                        'targetEntitySubType' => $indexingEntity->getTargetEntitySubtype(),
                        'siteId' => $indexingEntity->getSiteId(),
                    ],
                ],
            );
            throw new CouldNotDeleteException(
                $message,
                $exception,
                $exception->getCode(),
            );
        }
    }

    /**
     * @param int $indexingEntityId
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $indexingEntityId): void
    {
        $this->delete(
            $this->getById($indexingEntityId),
        );
    }

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
    ): int {
        $connection = $this->indexingEntityResourceModel->getConnection();
        $select = $connection->select();
        $select->from(
            $this->indexingEntityResourceModel->getTable(
                $this->indexingEntityResourceModel::TABLE,
            ),
            ['COUNT(*) as total'],
        );
        if ($siteId) {
            $select->where(IndexingEntity::SITE_ID . ' = ?', $siteId);
        }
        if ($entityType) {
            $select->where(IndexingEntity::TARGET_ENTITY_TYPE . ' = ?', $entityType);
        }
        if ($nextAction) {
            $select->where(IndexingEntity::NEXT_ACTION . ' = ?', $nextAction->value);
        }
        if (null !== $isIndexable) {
            $select->where(IndexingEntity::IS_INDEXABLE . ' = ?',
                $isIndexable
                    ? '1'
                    : '0');
        }

        return (int)$connection->fetchOne($select);
    }

    /**
     * @param string|null $siteId
     *
     * @return string[]
     */
    public function getUniqueEntityTypes(?string $siteId = null): array
    {
        $connection = $this->indexingEntityResourceModel->getConnection();
        $select = $connection->select();
        $select->distinct();
        $select->from(
            $this->indexingEntityResourceModel->getTable(
                $this->indexingEntityResourceModel::TABLE,
            ),
            [IndexingEntity::TARGET_ENTITY_TYPE],
        );
        if ($siteId) {
            $select->where(IndexingEntity::SITE_ID . ' = ?', $siteId);
        }

        return $connection->fetchCol($select);
    }
}
