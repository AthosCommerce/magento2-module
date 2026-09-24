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

namespace AthosCommerce\Feed\Plugin\Catalog;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Config as ConfigModel;
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Observer\BaseProductObserver;
use AthosCommerce\Feed\Service\Provider\ProductNextActionProvider;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Admin grid mass actions ("Update attributes", website assignment) write through
 * Product\Action without dispatching catalog_product_save_after, so the product observers
 * never see them. Queue the matching per-site actions here.
 */
class ProductActionPlugin
{
    private const ATTRIBUTES_AFFECTING_ACTION = ['status', 'visibility'];

    /**
     * @var BaseProductObserver
     */
    private $baseProductObserver;
    /**
     * @var ProductNextActionProvider
     */
    private $productNextActionProvider;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ConfigModel
     */
    private $configModel;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param BaseProductObserver $baseProductObserver
     * @param ProductNextActionProvider $productNextActionProvider
     * @param StoreManagerInterface $storeManager
     * @param ConfigModel $configModel
     * @param ResourceConnection $resourceConnection
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        BaseProductObserver $baseProductObserver,
        ProductNextActionProvider $productNextActionProvider,
        StoreManagerInterface $storeManager,
        ConfigModel $configModel,
        ResourceConnection $resourceConnection,
        AthosCommerceLogger $logger
    ) {
        $this->baseProductObserver = $baseProductObserver;
        $this->productNextActionProvider = $productNextActionProvider;
        $this->storeManager = $storeManager;
        $this->configModel = $configModel;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * @param ProductAction $subject
     * @param mixed $result
     * @param array $productIds
     * @param array $attrData
     * @param int|string $storeId
     * @return mixed
     */
    public function afterUpdateAttributes(
        ProductAction $subject,
        $result,
        $productIds,
        $attrData,
        $storeId
    ) {
        try {
            if (!array_intersect(array_keys((array)$attrData), self::ATTRIBUTES_AFFECTING_ACTION)) {
                return $result;
            }
            $productIds = $this->normalizeIds((array)$productIds);
            $storeId = (int)$storeId;
            // A store-view update only changes that store view; a default-scope update changes all.
            $stores = $storeId === Store::DEFAULT_STORE_ID
                ? $this->getLiveIndexingStores()
                : array_filter(
                    $this->getLiveIndexingStores(),
                    static fn (StoreInterface $store): bool => (int)$store->getId() === $storeId
                );
            foreach ($stores as $store) {
                $this->queueForStore($productIds, $store);
            }
        } catch (\Throwable $e) {
            $this->logger->error('[ProductActionPlugin] updateAttributes: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * @param ProductAction $subject
     * @param mixed $result
     * @param array $productIds
     * @param array $websiteIds
     * @param string $type
     * @return mixed
     */
    public function afterUpdateWebsites(
        ProductAction $subject,
        $result,
        $productIds,
        $websiteIds,
        $type
    ) {
        try {
            $productIds = $this->normalizeIds((array)$productIds);
            $websiteIds = $this->normalizeIds((array)$websiteIds);
            if (!$productIds || !$websiteIds) {
                return $result;
            }
            $stores = array_filter(
                $this->getLiveIndexingStores(),
                static fn (StoreInterface $store): bool => in_array((int)$store->getWebsiteId(), $websiteIds, true)
            );
            if ($type === 'add') {
                foreach ($stores as $store) {
                    $this->queueForStore($productIds, $store);
                }
            } elseif ($type === 'remove') {
                $this->queueWebsiteRemoval($productIds, $stores);
            }
        } catch (\Throwable $e) {
            $this->logger->error('[ProductActionPlugin] updateWebsites: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Upsert or Delete each product on the store's site, from its values in that store view.
     *
     * @param int[] $productIds
     * @param StoreInterface $store
     * @return void
     */
    private function queueForStore(array $productIds, StoreInterface $store): void
    {
        $siteId = $this->getSiteId($store);
        $byAction = [];
        foreach ($this->productNextActionProvider->getNextActionsByProductIds($productIds, (int)$store->getId())
                 as $productId => $nextAction) {
            $byAction[$nextAction][] = $productId;
        }
        foreach ($byAction as $nextAction => $ids) {
            $this->baseProductObserver->execute($ids, $nextAction, $nextAction === Actions::UPSERT, [$siteId]);
        }
        $this->logger->debug(
            '[ProductActionPlugin] queued',
            ['store_id' => $store->getId(), 'site_id' => $siteId, 'actions' => $byAction]
        );
    }

    /**
     * Delete on the sites of the removed websites, unless another website the product is still
     * assigned to serves the same site id.
     *
     * @param int[] $productIds
     * @param StoreInterface[] $removedStores
     * @return void
     */
    private function queueWebsiteRemoval(array $productIds, array $removedStores): void
    {
        $removedSiteIds = array_values(array_unique(array_map([$this, 'getSiteId'], $removedStores)));
        if (!$removedSiteIds) {
            return;
        }
        $siteIdsByWebsite = [];
        foreach ($this->getLiveIndexingStores() as $store) {
            $siteIdsByWebsite[(int)$store->getWebsiteId()][] = $this->getSiteId($store);
        }
        $remainingWebsites = $this->getProductWebsiteIds($productIds);

        $idsBySite = [];
        foreach ($productIds as $productId) {
            $stillServed = [];
            foreach ($remainingWebsites[$productId] ?? [] as $websiteId) {
                $stillServed = array_merge($stillServed, $siteIdsByWebsite[$websiteId] ?? []);
            }
            foreach (array_diff($removedSiteIds, $stillServed) as $siteId) {
                $idsBySite[$siteId][] = $productId;
            }
        }
        foreach ($idsBySite as $siteId => $ids) {
            $this->baseProductObserver->execute($ids, Actions::DELETE, false, [(string)$siteId]);
        }
        $this->logger->debug('[ProductActionPlugin] website removal queued Delete', ['sites' => $idsBySite]);
    }

    /**
     * @param int[] $productIds
     * @return array<int, int[]> product id => website ids
     */
    private function getProductWebsiteIds(array $productIds): array
    {
        $connection = $this->resourceConnection->getConnection();
        $rows = $connection->fetchAll(
            $connection->select()
                ->from($this->resourceConnection->getTableName('catalog_product_website'), ['product_id', 'website_id'])
                ->where('product_id IN (?)', $productIds)
        );
        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['product_id']][] = (int)$row['website_id'];
        }

        return $result;
    }

    /**
     * @return StoreInterface[]
     */
    private function getLiveIndexingStores(): array
    {
        return array_filter(
            $this->storeManager->getStores(false),
            fn (StoreInterface $store): bool => $this->configModel->isLiveIndexingEnabled((int)$store->getId())
                && $this->getSiteId($store) !== ''
        );
    }

    /**
     * @param StoreInterface $store
     * @return string
     */
    private function getSiteId(StoreInterface $store): string
    {
        return trim((string)$this->configModel->getSiteIdByStoreId((int)$store->getId()));
    }

    /**
     * @param array $ids
     * @return int[]
     */
    private function normalizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }
}
