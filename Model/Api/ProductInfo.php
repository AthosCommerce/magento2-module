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

namespace AthosCommerce\Feed\Model\Api;

use AthosCommerce\Feed\Api\ProductInfoInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\CollectionProcessor;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use AthosCommerce\Feed\Model\ItemsGenerator;
use AthosCommerce\Feed\Api\Data\ProductInfoResponseInterfaceFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

class ProductInfo implements ProductInfoInterface
{
    /**
     * @var CollectionProcessor
     */
    private $collectionProcessor;
    /**
     * @var ItemsGenerator
     */
    private $itemsGenerator;
    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var ProductInfoResponseInterfaceFactory
     */
    private $responseFactory;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param CollectionProcessor $collectionProcessor
     * @param ItemsGenerator $itemsGenerator
     * @param SpecificationBuilderInterface $specificationBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface $serializer
     * @param ProductInfoResponseInterfaceFactory $responseFactory
     */
    public function __construct(
        CollectionProcessor                 $collectionProcessor,
        ItemsGenerator                      $itemsGenerator,
        SpecificationBuilderInterface       $specificationBuilder,
        ScopeConfigInterface                $scopeConfig,
        SerializerInterface                 $serializer,
        ProductInfoResponseInterfaceFactory $responseFactory,
        AthosCommerceLogger                 $logger
    )
    {
        $this->collectionProcessor = $collectionProcessor;
        $this->itemsGenerator = $itemsGenerator;
        $this->specificationBuilder = $specificationBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
    }

    /**
     * @param int $productId
     * @param int $storeId
     *
     * @return \AthosCommerce\Feed\Api\Data\ProductInfoResponseInterface
     */
    public function getInfo(
        int $productId,
        int $storeId = 1
    ): \AthosCommerce\Feed\Api\Data\ProductInfoResponseInterface
    {
        /** @var \AthosCommerce\Feed\Api\Data\ProductInfoResponseInterface $response */
        $response = $this->responseFactory->create();

        try {
            $productIds = $this->getParentOrChildIds($productId);
            $response->setProductIds($productIds);

            $payload = $this->scopeConfig->getValue(
                \AthosCommerce\Feed\Helper\Constants::XML_PATH_LIVE_INDEXING_TASK_PAYLOAD,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );

            if (!$payload) {
                return $response
                    ->setProductInfo([])
                    ->setMessage(sprintf('No payload found for store ID %d', $storeId));
            }

            if (is_string($payload)) {
                $payload = $this->serializer->unserialize($payload);
            }
            if (!is_array($payload)) {
                return $response
                    ->setProductInfo([])
                    ->setMessage(sprintf('No payload found for store ID %d', $storeId));
            }

            $feedSpecification = $this->specificationBuilder->build($payload);
            $this->itemsGenerator->resetDataProviders($feedSpecification);

            $collection = $this->collectionProcessor->getCollection($feedSpecification);
            $collection->addFieldToFilter('entity_id', ['in' => $productIds]);
            $collection->load();

            $this->collectionProcessor->processAfterLoad($collection, $feedSpecification);
            $this->logger->info(
                'ProductInfoAPI started fetching product info',
                [
                    'product_ids' => $productIds,
                    'store_id' => $storeId
                ]
            );
            if (!$collection->getSize()) {
                return $response
                    ->setProductInfo([])
                    ->setMessage('No products found for the given product IDs');
            }

            $itemsData = $this->itemsGenerator->generate(
                $collection->getItems(),
                $feedSpecification
            );

            $this->itemsGenerator->resetDataProvidersAfterFetchItems($feedSpecification);
            $this->collectionProcessor->processAfterFetchItems($collection, $feedSpecification);

            $this->logger->debug(
                'ProductInfoAPI: ItemsData and Query',
                [
                    'query' => $collection->getSelect()->__toString(),
                    'items_data' => $itemsData
                ]
            );

            $response->setProductInfo($itemsData);
        } catch (\Exception $e) {
            $this->logger->error(
                'ProductInfoAPI: Failed to fetch product info',
                [
                    'product_ids' => $productIds,
                    'store_id' => $storeId,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            );
            return $response
                ->setProductInfo([])
                ->setMessage($e->getMessage());
        }
        $this->logger->info(
            'ProductInfoAPI completed fetching product info',
            [
                'product_ids' => $productIds,
                'store_id' => $storeId
            ]
        );
        return $response;
    }

    /**
     * @param int $productId
     *
     * @return array
     */
    private function getParentOrChildIds(int $productId): array
    {
        $ids = [$productId];

        try {
            $childIds = [];
            $parentIds = [];
            $groupedParentIds = [];
            $groupedChildrenIds = [];
            //\AthosCommerce\Feed\Model\Feed\DataProvider\Parent\RelationsProvider::getConfigurableRelationIds
            //\AthosCommerce\Feed\Model\Feed\DataProvider\Parent\RelationsProvider::getGroupRelationIds
            //\AthosCommerce\Feed\Model\Feed\DataProvider\Parent\RelationsProvider::getConfigurableChildrenIds
            //\AthosCommerce\Feed\Model\Feed\DataProvider\Parent\RelationsProvider::getGroupedChildrenIds

            $childIds = array_merge(...
                array_values($childIds
                    ?: []));
            $ids = array_merge(
                $ids,
                $parentIds
                    ?: [],
                $groupedParentIds
                    ?: [],
                $childIds
                    ?: [],
                $groupedChildrenIds
                    ?: []
            );
        } catch (Exception $e) {
            $this->logger->error(
                'ProductInfoAPI: Failed to resolve parent/child IDs',
                [
                    'method' => __METHOD__,
                    'productId' => $productId,
                    'message' => $e->getMessage(),
                ]
            );
        }

        return array_unique(array_filter($ids));
    }
}
