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
use AthosCommerce\Feed\Model\CollectionProcessor;
use AthosCommerce\Feed\Model\ItemsGenerator;
use AthosCommerce\Feed\Api\AppConfigInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Api\GenerateFeedInterface;
use AthosCommerce\Feed\Api\MetadataInterface;
use AthosCommerce\Feed\Model\Feed\CollectionConfigInterface;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\StorageInterface;
use AthosCommerce\Feed\Model\Feed\SystemFieldsList;
use AthosCommerce\Feed\Model\Metric\CollectorInterface;
use AthosCommerce\Feed\Api\TaskRepositoryInterface;
use Psr\Log\LoggerInterface;

class GenerateFeed implements GenerateFeedInterface
{
    /**
     * @var CollectionConfigInterface
     */
    private $collectionConfig;
    /**
     * @var StorageInterface
     */
    private $storage;
    /**
     * @var ContextManagerInterface
     */
    private $contextManager;
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
    /**
     * @var string
     */
    private $productCount = '';
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ItemsGenerator
     */
    private $itemsGenerator;
    /**
     * @var CollectionProcessor
     */
    private $collectionProcessor;

    /**
     * @param CollectionProcessor $collectionProcessor
     * @param ItemsGenerator $itemsGenerator
     * @param CollectionConfigInterface $collectionConfig
     * @param StorageInterface $storage
     * @param ContextManagerInterface $contextManager
     * @param CollectorInterface $metricCollector
     * @param AppConfigInterface $appConfig
     * @param TaskRepositoryInterface $taskRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        CollectionProcessor $collectionProcessor,
        ItemsGenerator $itemsGenerator,
        CollectionConfigInterface $collectionConfig,
        StorageInterface $storage,
        ContextManagerInterface $contextManager,
        CollectorInterface $metricCollector,
        AppConfigInterface $appConfig,
        TaskRepositoryInterface $taskRepository,
        LoggerInterface $logger
    ) {
        $this->collectionProcessor = $collectionProcessor;
        $this->itemsGenerator = $itemsGenerator;
        $this->collectionConfig = $collectionConfig;
        $this->storage = $storage;
        $this->contextManager = $contextManager;
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
    public function execute(FeedSpecificationInterface $feedSpecification, int $id): void
    {
        $format = $feedSpecification->getFormat();
        $this->logger->info('Product feed generation started with entity id', [
            'method' => __METHOD__,
            'entityId' => $id,
            'format' => $format,
        ]);
        $startTime = microtime(true);
        $isPreSignUrlFileFormatStatus = $this->setPresignUrlFileFormat($feedSpecification);
        if (false === $isPreSignUrlFileFormatStatus) {
            $this->logger->error(
                'Not able to set the format from PreSignedUrl',
                [
                    'method' => __METHOD__,
                    'entityId' => $id,
                    'format' => $format,
                    'feedSpecification' => $feedSpecification,
                ]
            );
            throw new Exception((string)__('Not able to set the format(%1) from PreSignedUrl', $format));
        }

        if (!$this->storage->isSupportedFormat($format)) {
            $this->logger->error('Feed format not supported', [
                'method' => __METHOD__,
                'entityId' => $id,
                'format' => $format,
            ]);
            throw new Exception((string)__('%1 is not supported format', $format));
        }

        $this->initialize($feedSpecification);
        $collection = $this->collectionProcessor->getCollection($feedSpecification);

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
            'pageSize' => $pageSize,
            'pageCount' => $pageCount,
        ]);
        while ($currentPageNumber <= $pageCount) {
            try {
                $collection->setCurPage($currentPageNumber);
                $collection->load();
                $this->collectionProcessor->processAfterLoad($collection, $feedSpecification);

                if ($currentPageNumber === 1) {
                    $this->logger->info(
                        'Product collection after loading',
                        [
                            'method' => __METHOD__,
                            'entityId' => $id,
                            'query' => $collection->getSelect()->__toString(),
                        ]
                    );
                }
                $itemsData = $this->itemsGenerator->generate($collection->getItems(), $feedSpecification);
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
                $this->itemsGenerator->resetDataProvidersAfterFetchItems($feedSpecification);
                $collection->clear();
                $this->collectionProcessor->processAfterFetchItems($collection, $feedSpecification);

                gc_collect_cycles();
            } catch (Exception $exception) {
                $this->storage->rollback();
                $this->logger->error('Error fetching products details',
                    [
                        'method' => __METHOD__,
                        'entityId' => $id,
                        'currentPage' => $currentPageNumber,
                        'format' => $format,
                        'query' => $collection->getSelect()->__toString(),
                        'message' => $exception,
                    ]
                );
                throw $exception;
            }
        }
        $endTime = microtime(true);
        $this->logger->info('Product feed generation completed successfully',
            [
                'method' => __METHOD__,
                'entityId' => $id,
                'query' => $collection->getSelect()->__toString(),
                'format' => $format,
            ]
        );
        $this->logger->debug('Product feed generation time taken execution',
            [
                'startTime' => $startTime,
                'endTime' => $endTime,
                'totalSeconds' => ($endTime - $startTime),
            ]
        );

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
        $this->itemsGenerator->resetDataProviders($feedSpecification);
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
        $this->itemsGenerator->resetDataProviders($feedSpecification);
        $this->collectMetrics('Before Send File');
        $this->logger->info('File storage in s3 started', [
            'method' => __METHOD__,
            'entityId' => $id,
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
            'entityId' => $id,
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
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return void
     */
    private function setPresignUrlFileFormat(FeedSpecificationInterface $feedSpecification): bool
    {
        $urlPath = parse_url($feedSpecification->getPreSignedUrl(), PHP_URL_PATH);
        if (!$urlPath) {
            return false;
        }
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

        return true;
    }
}
