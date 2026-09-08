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

namespace AthosCommerce\Feed\Model\LiveIndexing;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Config as ConfigModel;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Service\Provider\IndexingEntityProvider;
use Magento\Framework\Serialize\SerializerInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Orchestrates the live-indexing run for a single store.
 *
 * Responsibilities:
 *  1. Validate and deserialise store configuration safely.
 *  2. Build the feed specification and set the request context.
 *  3. Fetch pending DELETE and UPSERT records, respecting the per-minute rate window.
 *  4. Delegate processing to DeleteProcessor and UpdateProcessor.
 *  5. Log the overall summary and reset context.
 */
class Processor
{
    /**
     * @var IndexingEntityProvider
     */
    private $indexingEntityProvider;

    /**
     * @var ConfigModel
     */
    private $config;

    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ContextManagerInterface
     */
    private $contextManager;

    /**
     * @var DeleteProcessor
     */
    private $deleteProcessor;

    /**
     * @var UpdateProcessor
     */
    private $updateProcessor;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param IndexingEntityProvider $indexingEntityProvider
     * @param ConfigModel $config
     * @param SpecificationBuilderInterface $specificationBuilder
     * @param SerializerInterface $serializer
     * @param ContextManagerInterface $contextManager
     * @param DeleteProcessor $deleteProcessor
     * @param UpdateProcessor $updateProcessor
     * @param AthosCommerceLogger $logger
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        IndexingEntityProvider        $indexingEntityProvider,
        ConfigModel                   $config,
        SpecificationBuilderInterface $specificationBuilder,
        SerializerInterface           $serializer,
        ContextManagerInterface       $contextManager,
        DeleteProcessor               $deleteProcessor,
        UpdateProcessor               $updateProcessor,
        AthosCommerceLogger           $logger,
        StoreManagerInterface         $storeManager
    ) {
        $this->indexingEntityProvider = $indexingEntityProvider;
        $this->config = $config;
        $this->specificationBuilder = $specificationBuilder;
        $this->serializer = $serializer;
        $this->contextManager = $contextManager;
        $this->deleteProcessor = $deleteProcessor;
        $this->updateProcessor = $updateProcessor;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
    }

    /**
     * @param StoreInterface|mixed $store
     * @param string $siteId
     *
     * @return int Total number of successfully processed entities (deletes + upserts)
     */
    public function execute($store, string $siteId): int
    {
        // 1. Guard against uninitialized or missing store instance during deployment setup
        if (!$store || !$store->getId()) {
            $this->logger->error('[LiveIndexing] Invalid or uninitialized store instance provided.');
            return 0;
        }

        try {
            $this->storeManager->getStore($store->getId());
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('[LiveIndexing] Store ID %s is not defined in current deployment context: %s', $store->getId(), $e->getMessage())
            );
            return 0;
        }

        $storeId = (int)$store->getId();
        $storeCode = $store->getCode();

        $feedSpecification = $this->buildFeedSpecification($storeId, $storeCode);
        if ($feedSpecification === null) {
            $this->logger->info(
                sprintf('[LiveIndexing] Feed specification is null for store:%s | siteId:%s', $storeCode, $siteId)
            );
            return 0;
        }

        $this->logger->info(
            sprintf('[LiveIndexing] Feed specification built for store:%s | siteId:%s', $storeCode, $siteId)
        );

        $this->contextManager->setContextFromSpecification($feedSpecification);
        $this->logger->debug(
            sprintf('[LiveIndexing] Context set for store:%s | siteId:%s', $storeCode, $siteId)
        );

        // 3. Fallback to a non-zero integer if the store configuration is zero or empty
        $configuredPerMinute = $this->config->getRequestPerMinuteByStoreId($storeId);
        $perMinute = max(1, (int)$configuredPerMinute);
        $maxLimit = $perMinute * 2; // 2-minute window to avoid rate-limiting at the receiving end

        $this->logger->info(
            sprintf(
                '[LiveIndexing] Initiated for store:%s | siteId:%s | requests:%s | maxLimit:%s',
                $storeCode,
                $siteId,
                $perMinute,
                $maxLimit
            )
        );

        // --- DELETE ---
        $deleteRecords = $this->indexingEntityProvider->get(
            null, [$siteId], null, Actions::DELETE, null, null, $maxLimit
        );
        $deleteCount = count($deleteRecords);

        $this->logger->info(
            sprintf('[LiveIndexing] Delete IDs summary | Store: %s | Count: %s', $storeCode, $deleteCount)
        );
        $this->logger->debug(
            '[LiveIndexing][Processor][QueueState]',
            [
                'siteId' => $siteId,
                'store' => $storeCode,
                'deletePendingCount' => $deleteCount,
                'updatePendingCount' => 0,
                'deleteSkipped' => false,
                'updateSkipped' => false,
            ]
        );

        $deleteSuccessCount = $this->deleteProcessor->execute($deleteRecords, $siteId, $storeCode);

        // --- UPSERT (skipped when deletes saturate the rate window) ---
        $updateSuccessCount = 0;

        if ($deleteCount >= $maxLimit) {
            $this->logger->info(
                '[LiveIndexing] Skipping Update operation fully because deletes saturate window',
                [
                    'siteId' => $siteId,
                    'store' => $storeCode,
                    'deleteCount' => $deleteCount,
                    'maxLimit' => $maxLimit,
                ]
            );
            $this->logger->debug(
                '[LiveIndexing][Processor][QueueState]',
                [
                    'siteId' => $siteId,
                    'store' => $storeCode,
                    'deletePendingCount' => $deleteCount,
                    'updatePendingCount' => 0,
                    'deleteSkipped' => false,
                    'updateSkipped' => true,
                ]
            );
        } else {
            $remainingRequests = (int)max(0, $maxLimit - $deleteCount);
            if ($remainingRequests > 0) {
                $this->logger->info(
                    '[LiveIndexing] Fetching indexable Update IDs',
                    [
                        'siteId' => $siteId,
                        'store' => $storeCode,
                        'remainingRequests' => $remainingRequests,
                    ]
                );

                $updateRecords = $this->indexingEntityProvider->get(
                    null, [$siteId], null, Actions::UPSERT, true, null, $remainingRequests
                );

                $this->logger->info(
                    sprintf(
                        '[LiveIndexing] Update IDs summary | Store: %s | SiteId: %s | Count: %s',
                        $storeCode,
                        $siteId,
                        count($updateRecords)
                    )
                );

                $this->logger->debug(
                    '[LiveIndexing][Processor][QueueState]',
                    [
                        'siteId' => $siteId,
                        'store' => $storeCode,
                        'deletePendingCount' => $deleteCount,
                        'updatePendingCount' => count($updateRecords),
                        'deleteSkipped' => false,
                        'updateSkipped' => false,
                    ]
                );

                $updateSuccessCount = $this->updateProcessor->execute(
                    $updateRecords,
                    $store,
                    $siteId,
                    $feedSpecification
                );
            } else {
                $this->logger->debug(
                    '[LiveIndexing][Processor][QueueState]',
                    [
                        'siteId' => $siteId,
                        'store' => $storeCode,
                        'deletePendingCount' => $deleteCount,
                        'updatePendingCount' => 0,
                        'deleteSkipped' => false,
                        'updateSkipped' => true,
                    ]
                );
            }
        }

        $totalSuccessCount = $deleteSuccessCount + $updateSuccessCount;

        $this->logger->info(
            '[LiveIndexing] Summary',
            [
                'siteId' => $siteId,
                'store' => $storeCode,
                'totalSuccessCount' => $totalSuccessCount,
            ]
        );

        $this->contextManager->resetContext();

        return $totalSuccessCount;
    }

    /**
     * Validates and deserialises the payload config, then builds a FeedSpecification.
     * Returns null and logs an error if the config is missing or invalid.
     *
     * @param int $storeId
     * @param string $storeCode
     *
     * @return FeedSpecificationInterface|null
     */
    private function buildFeedSpecification(int $storeId, string $storeCode): ?FeedSpecificationInterface
    {
        $payloadConfig = $this->config->getPayloadByStoreId($storeId);

        if (!$payloadConfig) {
            $this->logger->error('Missing payload config', ['store' => $storeCode]);
            return null;
        }

        // 2. Safe Payload Unserialization with Catch Guards
        if (is_string($payloadConfig)) {
            try {
                $payloadConfig = $this->serializer->unserialize($payloadConfig);
            } catch (\InvalidArgumentException $e) {
                $this->logger->error(
                    'Failed to unserialize payload config due to invalid string format',
                    ['store' => $storeCode, 'error' => $e->getMessage()]
                );
                return null;
            } catch (\Throwable $e) {
                $this->logger->error(
                    'Unexpected error during payload config unserialization',
                    ['store' => $storeCode, 'error' => $e->getMessage()]
                );
                return null;
            }
        }

        if (!is_array($payloadConfig)) {
            $this->logger->error(
                'Invalid payload config type',
                [
                    'store' => $storeCode,
                    'payloadConfig' => $payloadConfig,
                    'getType' => gettype($payloadConfig),
                ]
            );
            return null;
        }

        $payloadConfig['store'] = $storeCode;

        $feedSpecification = $this->specificationBuilder->build($payloadConfig);
        $feedSpecification->setStoreCode($storeCode);
        $feedSpecification->setIndexingMode(FeedSpecificationInterface::LIVE_MODE);

        return $feedSpecification;
    }
}
