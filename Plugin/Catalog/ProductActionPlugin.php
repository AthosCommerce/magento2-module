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
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Observer\BaseProductObserver;
use AthosCommerce\Feed\Service\Action\SyncSiteAssignmentAction;
use AthosCommerce\Feed\Service\Provider\LiveIndexingSiteProvider;
use AthosCommerce\Feed\Service\Provider\ProductNextActionProvider;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Queues live indexing actions for admin grid mass actions.
 *
 * "Update attributes" and website assignment write through Product\Action without dispatching
 * catalog_product_save_after, so the product observers never see them.
 */
class ProductActionPlugin
{
    private const SCOPE_STORE = 0;
    private const SCOPE_WEBSITE = 1;
    private const SCOPE_GLOBAL = 2;

    /**
     * @var BaseProductObserver
     */
    private $baseProductObserver;
    /**
     * @var ProductNextActionProvider
     */
    private $productNextActionProvider;
    /**
     * @var LiveIndexingSiteProvider
     */
    private $siteProvider;
    /**
     * @var SyncSiteAssignmentAction
     */
    private $syncSiteAssignmentAction;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var EavConfig
     */
    private $eavConfig;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param BaseProductObserver $baseProductObserver
     * @param ProductNextActionProvider $productNextActionProvider
     * @param LiveIndexingSiteProvider $siteProvider
     * @param SyncSiteAssignmentAction $syncSiteAssignmentAction
     * @param StoreManagerInterface $storeManager
     * @param EavConfig $eavConfig
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        BaseProductObserver $baseProductObserver,
        ProductNextActionProvider $productNextActionProvider,
        LiveIndexingSiteProvider $siteProvider,
        SyncSiteAssignmentAction $syncSiteAssignmentAction,
        StoreManagerInterface $storeManager,
        EavConfig $eavConfig,
        AthosCommerceLogger $logger
    ) {
        $this->baseProductObserver = $baseProductObserver;
        $this->productNextActionProvider = $productNextActionProvider;
        $this->siteProvider = $siteProvider;
        $this->syncSiteAssignmentAction = $syncSiteAssignmentAction;
        $this->storeManager = $storeManager;
        $this->eavConfig = $eavConfig;
        $this->logger = $logger;
    }

    /**
     * Queue per-site actions after a mass attribute update.
     *
     * @param ProductAction $subject
     * @param mixed $result
     * @param array $productIds
     * @param array $attrData
     * @param int|string $storeId
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterUpdateAttributes(
        ProductAction $subject,
        $result,
        $productIds,
        $attrData,
        $storeId
    ) {
        try {
            $productIds = $this->normalizeIds((array)$productIds);
            if (!$productIds || !$attrData) {
                return $result;
            }
            foreach ($this->getStoresAffectedByUpdate(array_keys((array)$attrData), (int)$storeId) as $store) {
                $this->queueForStore($productIds, $store);
            }
        } catch (\Throwable $e) {
            $this->logger->error('[ProductActionPlugin] updateAttributes: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Queue per-site actions after products are added to or removed from websites.
     *
     * @param ProductAction $subject
     * @param mixed $result
     * @param array $productIds
     * @param array $websiteIds
     * @param string $type
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
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
                $this->siteProvider->getLiveIndexingStores(),
                static fn (StoreInterface $store): bool => in_array((int)$store->getWebsiteId(), $websiteIds, true)
            );
            if ($type === 'add') {
                foreach ($stores as $store) {
                    $this->queueForStore($productIds, $store, true);
                }
            } elseif ($type === 'remove') {
                $siteIds = array_map(
                    fn (StoreInterface $store): string => $this->siteProvider->getSiteId((int)$store->getId()),
                    $stores
                );
                $this->syncSiteAssignmentAction->queueDeleteForUnassignedSites(
                    $productIds,
                    array_values(array_unique($siteIds))
                );
            }
        } catch (\Throwable $e) {
            $this->logger->error('[ProductActionPlugin] updateWebsites: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Live indexing store views whose values an update at this scope changes.
     *
     * A store-view update of a website-scoped attribute is written to every store view of that
     * website, and a global attribute to all store views, so the widest scope decides.
     *
     * @param string[] $attributeCodes
     * @param int $storeId
     * @return StoreInterface[]
     */
    private function getStoresAffectedByUpdate(array $attributeCodes, int $storeId): array
    {
        $stores = $this->siteProvider->getLiveIndexingStores();
        if ($storeId === Store::DEFAULT_STORE_ID) {
            return $stores;
        }
        $scope = self::SCOPE_STORE;
        foreach ($attributeCodes as $code) {
            $scope = max($scope, $this->getAttributeScope((string)$code));
        }
        if ($scope === self::SCOPE_GLOBAL) {
            return $stores;
        }
        $websiteId = (int)$this->storeManager->getStore($storeId)->getWebsiteId();

        return array_filter(
            $stores,
            static fn (StoreInterface $store): bool => $scope === self::SCOPE_WEBSITE
                ? (int)$store->getWebsiteId() === $websiteId
                : (int)$store->getId() === $storeId
        );
    }

    /**
     * Scope of a product attribute; unknown codes count as global.
     *
     * @param string $code
     * @return int
     */
    private function getAttributeScope(string $code): int
    {
        $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $code);
        if (!$attribute || !$attribute->getId() || !method_exists($attribute, 'isScopeStore')) {
            return self::SCOPE_GLOBAL;
        }
        if ($attribute->isScopeStore()) {
            return self::SCOPE_STORE;
        }

        return $attribute->isScopeWebsite() ? self::SCOPE_WEBSITE : self::SCOPE_GLOBAL;
    }

    /**
     * Upsert or Delete each product on the store's site, from its values in that store view.
     *
     * @param int[] $productIds
     * @param StoreInterface $store
     * @param bool $addMissingRows create rows for products new to this site
     * @return void
     */
    private function queueForStore(array $productIds, StoreInterface $store, bool $addMissingRows = false): void
    {
        $siteId = $this->siteProvider->getSiteId((int)$store->getId());
        $byAction = [];
        $nextActions = $this->productNextActionProvider->getNextActionsByProductIds(
            $productIds,
            (int)$store->getId()
        );
        foreach ($nextActions as $productId => $nextAction) {
            $byAction[$nextAction][] = $productId;
        }
        if ($addMissingRows && !empty($byAction[Actions::UPSERT])) {
            $this->syncSiteAssignmentAction->addMissingRows($byAction[Actions::UPSERT], $siteId);
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
     * Unique positive integer ids.
     *
     * @param array $ids
     * @return int[]
     */
    private function normalizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }
}
