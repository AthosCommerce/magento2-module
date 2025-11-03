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

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use AthosCommerce\Feed\Api\AppConfigInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Api\GenerateFeedInterface;
use AthosCommerce\Feed\Api\MetadataInterface;
use AthosCommerce\Feed\Model\Feed\Collection\ProcessorPool;
use AthosCommerce\Feed\Model\Feed\CollectionConfigInterface;
use AthosCommerce\Feed\Model\Feed\CollectionProviderInterface;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use AthosCommerce\Feed\Model\Feed\DataProviderPool;
use AthosCommerce\Feed\Model\Feed\StorageInterface;
use AthosCommerce\Feed\Model\Feed\SystemFieldsList;
use AthosCommerce\Feed\Model\Metric\CollectorInterface;
use AthosCommerce\Feed\Api\TaskRepositoryInterface;
use Psr\Log\LoggerInterface;

class GenerateFeed implements GenerateFeedInterface
{
    /**
     * @var CollectionProviderInterface
     */
    private $collectionProvider;
    /**
     * @var DataProviderPool
     */
    private $dataProviderPool;
    /**
     * @var CollectionConfigInterface
     */
    private $collectionConfig;
    /**
     * @var StorageInterface
     */
    private $storage;
    /**
     * @var SystemFieldsList
     */
    private $systemFieldsList;
    /**
     * @var ContextManagerInterface
     */
    private $contextManager;
    /**
     * @var ProcessorPool
     */
    private $afterLoadProcessorPool;
    /**
     * @var CollectorInterface
     */
    private $metricCollector;
    /**
     * @var AppConfigInterface
     */
    private $appConfig;

    private $gcStatus = false;

    /**
     * @var TaskRepository
     */
    private $taskRepository;
    private $productCount = '';
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * GenerateFeed constructor.
     *
     * @param CollectionProviderInterface $collectionProvider
     * @param DataProviderPool $dataProviderPool
     * @param CollectionConfigInterface $collectionConfig
     * @param StorageInterface $storage
     * @param SystemFieldsList $systemFieldsList
     * @param ContextManagerInterface $contextManager
     * @param ProcessorPool $afterLoadProcessorPool
     * @param CollectorInterface $metricCollector
     * @param AppConfigInterface $appConfig
     * @param TaskRepositoryInterface $taskRepository
     */
    public function __construct(
        CollectionProviderInterface $collectionProvider,
        DataProviderPool $dataProviderPool,
        CollectionConfigInterface $collectionConfig,
        StorageInterface $storage,
        SystemFieldsList $systemFieldsList,
        ContextManagerInterface $contextManager,
        ProcessorPool $afterLoadProcessorPool,
        CollectorInterface $metricCollector,
        AppConfigInterface $appConfig,
        TaskRepositoryInterface $taskRepository,
        LoggerInterface $logger
    ) {
        $this->collectionProvider = $collectionProvider;
        $this->dataProviderPool = $dataProviderPool;
        $this->collectionConfig = $collectionConfig;
        $this->storage = $storage;
        $this->systemFieldsList = $systemFieldsList;
        $this->contextManager = $contextManager;
        $this->afterLoadProcessorPool = $afterLoadProcessorPool;
        $this->metricCollector = $metricCollector;
        $this->appConfig = $appConfig;
        $this->taskRepository = $taskRepository;
        $this->logger = $logger;
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @throws Exception
     */
    public function execute(FeedSpecificationInterface $feedSpecification, $id): void
    {
        $this->logger->info('Product feed generation started with entity id', [
            'method' => __METHOD__,
            'entityId'=> $id,
            'format' => $feedSpecification->getFormat(),
        ]);
        $this->setPresignUrlFileFormat($feedSpecification);
        $format = $feedSpecification->getFormat();
        if (!$this->storage->isSupportedFormat($format)) {
            $this->logger->error('Feed format not supported', [
                'method' => __METHOD__,
                'format' => $format,
            ]);
            throw new Exception((string)__('%1 is not supported format', $format));
        }

        $this->initialize($feedSpecification);
        $collection = $this->collectionProvider->getCollection($feedSpecification);
        $pageSize = $this->collectionConfig->getPageSize();
        $collection->setPageSize($pageSize);
        $pageCount = $this->getPageCount($collection);
        $currentPageNumber = 1;
        $metricPage = 1;
        $metricMaxPage = $this->appConfig->getValue('product_metric_max_page') ?? 10;
        $metrics = 0;
        $this->collectMetrics('Before Start Items Generation');
        $productCount = 0;
        $this->logger->info('Product collection details', [
            'method' => __METHOD__,
            'pageSize'=> $pageSize,
            'pageCount' => $pageCount,
        ]);
        while ($currentPageNumber <= $pageCount) {
            try {
                $collection->setCurPage($currentPageNumber);
                $collection->load();
                $this->processAfterLoad($collection, $feedSpecification);
                $itemsData = $this->getItemsData($collection->getItems(), $feedSpecification);
                $productCount += count($itemsData);
                $title = 'Products: ' . $pageSize * $metrics . ' - ' . $pageSize * ($metrics + 1);
                $metrics++;
                if ($metricPage === (int)$metricMaxPage) {
                    $this->collectMetrics($title, $itemsData);
                    $metricPage = 1;
                } else {
                    $this->collectMetrics($title, $itemsData, false);
                    $metricPage++;
                }

                $this->storage->addData($itemsData, $id);
                $itemsData = [];
                $currentPageNumber++;
                $this->resetDataProvidersAfterFetchItems($feedSpecification);
                $collection->clear();
                $this->processAfterFetchItems($collection, $feedSpecification);
                gc_collect_cycles();
            } catch (Exception $exception) {
                $this->storage->rollback();
                $this->logger->error('Error fetching products details', [
                    'method' => __METHOD__,
                    'entityId'=> $id,
                    'format' => $feedSpecification->getFormat(),
                    'message' => $exception
                ]);
                throw $exception;
            }
        }
        $this->logger->info('Product feed generation completed successfully', [
            'method' => __METHOD__,
            'entityId'=> $id,
            'format' => $feedSpecification->getFormat(),
        ]);
        $task = $this->taskRepository->get($id);
        $task->setProductCount($productCount);
        $this->taskRepository->save($task);
        $this->reset($feedSpecification, $id);

        return;
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     */
    private function initialize(FeedSpecificationInterface $feedSpecification): void
    {
        $this->gcStatus = gc_enabled();
        if (!$this->gcStatus) {
            gc_enable();
        }

        $this->collectMetrics('Initial');
        $this->resetDataProviders($feedSpecification);
        $this->contextManager->setContextFromSpecification($feedSpecification);
        $this->storage->initiate($feedSpecification);
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     * @param $id
     *
     * @throws Exception
     */
    private function reset(FeedSpecificationInterface $feedSpecification, int $id): void
    {
        $this->resetDataProviders($feedSpecification);
        $this->collectMetrics('Before Send File');
        $this->logger->info('File storage in s3 started', [
            'method' => __METHOD__,
            'entityId'=> $id,
            'format' => $feedSpecification->getFormat(),
        ]);
        try {
            $this->storage->commit($id);
        } finally {
            $this->collectMetrics('After Send File');
            $this->metricCollector->print(
                CollectorInterface::CODE_PRODUCT_FEED,
                CollectorInterface::PRINT_TYPE_FULL
            );
        }
        $this->logger->info('File storage in s3 completed', [
            'method' => __METHOD__,
            'entityId'=> $id,
            'format' => $feedSpecification->getFormat(),
        ]);
        $this->metricCollector->reset(CollectorInterface::CODE_PRODUCT_FEED);
        $this->contextManager->resetContext();
        if (!$this->gcStatus) {
            gc_disable();
        }
    }

    /**
     * @param string $title
     * @param array|null $itemsData
     * @param bool $print
     */
    private function collectMetrics(string $title, ?array $itemsData = null, bool $print = true): void
    {
        $data = [];
        try {
            $storageAdditionalData = $this->storage->getAdditionalData();
        } catch (\Throwable $exception) {
            $storageAdditionalData = [];
        }

        if (isset($storageAdditionalData['name'])) {
            $data['name'] = [
                'static' => true,
                'value' => $storageAdditionalData['name'],
            ];
        }

        if (isset($storageAdditionalData['size'])) {
            $data['size'] = $storageAdditionalData['size'];
        }

        if (!is_null($itemsData)) {
            $itemsDataSize = round(mb_strlen(json_encode($itemsData), '8bit') / 1024 / 1024, 4);
            $itemsDataCount = count($itemsData);
            $data['items_data_size'] = $itemsDataSize;
            $data['items_data_count'] = $itemsDataCount;
        }

        $this->metricCollector->collect(CollectorInterface::CODE_PRODUCT_FEED, $title, $data);
        if ($print) {
            $this->metricCollector->print(CollectorInterface::CODE_PRODUCT_FEED);
        }
    }

    /**
     * @param Collection $collection
     *
     * @return int
     * @throws FileSystemException
     * @throws RuntimeException
     */
    private function getPageCount(Collection $collection): int
    {
        $pageCount = null;
        if ($this->appConfig->isDebug()) {
            $pageCount = $this->appConfig->getValue('product_page_count');
        }
        if (is_null($pageCount)) {
            $pageCount = $collection->getLastPageNumber();
        }

        return (int)$pageCount;
    }

    /**
     * @param Collection $collection
     * @param FeedSpecificationInterface $feedSpecification
     */
    private function processAfterLoad(Collection $collection, FeedSpecificationInterface $feedSpecification): void
    {
        foreach ($this->afterLoadProcessorPool->getAll() as $processor) {
            $processor->processAfterLoad($collection, $feedSpecification);
        }
    }

    /**
     * @param Collection $collection
     * @param FeedSpecificationInterface $feedSpecification
     */
    private function processAfterFetchItems(Collection $collection, FeedSpecificationInterface $feedSpecification): void
    {
        foreach ($this->afterLoadProcessorPool->getAll() as $processor) {
            $processor->processAfterFetchItems($collection, $feedSpecification);
        }
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     */
    private function resetDataProviders(FeedSpecificationInterface $feedSpecification): void
    {
        $dataProviders = $this->getDataProviders($feedSpecification);
        foreach ($dataProviders as $dataProvider) {
            $dataProvider->reset();
        }
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     */
    private function resetDataProvidersAfterFetchItems(FeedSpecificationInterface $feedSpecification): void
    {
        $dataProviders = $this->getDataProviders($feedSpecification);
        foreach ($dataProviders as $dataProvider) {
            $dataProvider->resetAfterFetchItems();
        }
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return DataProviderInterface[]
     */
    private function getDataProviders(FeedSpecificationInterface $feedSpecification): array
    {
        $ignoredFields = $feedSpecification->getIgnoreFields();

        return $this->dataProviderPool->get($ignoredFields);
    }

    /**
     * @param Product[] $items
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     */
    private function getItemsData(array $items, FeedSpecificationInterface $feedSpecification): array
    {
        if (empty($items)) {
            return [];
        }

        $data = [];
        foreach ($items as $item) {
            $data[] = [
                'entity_id' => $item->getEntityId(),
                'product_model' => $item,
            ];
        }

        $this->systemFieldsList->add('product_model');
        $dataProviders = $this->getDataProviders($feedSpecification);
        foreach ($dataProviders as $dataProvider) {
            $data = $dataProvider->getData($data, $feedSpecification);
        }

        $data = $this->cleanupItemsData($data);

        return $data;
    }

    /**
     * @param array $items
     *
     * @return array
     */
    private function cleanupItemsData(array $items): array
    {
        $systemFields = $this->systemFieldsList->get();
        foreach ($items as &$item) {
            foreach ($systemFields as $field) {
                if (isset($item[$field])) {
                    unset($item[$field]);
                }
            }
        }

        return $items;
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return void
     */
    private function setPresignUrlFileFormat(FeedSpecificationInterface $feedSpecification): void
    {
        $urlPath = parse_url($feedSpecification->getPreSignedUrl(), PHP_URL_PATH);
        $fileBaseExtension = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));
        $secondExtension = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));

        // Check if file has a "gz" extension and process the format accordingly
        if ($fileBaseExtension === MetadataInterface::FORMAT_JSON) {
            $feedSpecification->setFormat($fileBaseExtension); // Set format as json based on URL extension
        } elseif ($secondExtension === MetadataInterface::FORMAT_GZ) {
            if (str_contains($urlPath, MetadataInterface::FORMAT_JSON_GZ)) {
                $feedSpecification->setFormat(MetadataInterface::FORMAT_JSON); // For json.gz, treat as JSON format
            }
        } else {
            $feedSpecification->setFormat($fileBaseExtension);
        }
    }
}
