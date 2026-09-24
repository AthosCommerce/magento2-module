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

namespace AthosCommerce\Feed\Observer\Product;

use AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface;
use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Model\Config as ConfigModel;
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Service\Tracking\IdProviderInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use AthosCommerce\Feed\Observer\BaseProductObserver;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Service\Provider\ProductNextActionProvider;
use AthosCommerce\Feed\Service\Action\SyncSiteAssignmentAction;

class UpdateObserver implements ObserverInterface
{
    private const SCOPE_STORE = 0;
    private const SCOPE_WEBSITE = 1;
    private const SCOPE_GLOBAL = 2;

    /**
     * Data keys that change on every save without changing what is sent to Athos.
     * Stock is handled by InventoryUpdateObserver.
     */
    private const IGNORED_CHANGE_KEYS = [
        'updated_at', 'quantity_and_stock_status', 'stock_item', 'stock_data', 'id', 'entity_id',
        'row_id', 'store_id', 'store_ids', '_edit_mode', 'media_attributes', 'can_save_custom_options',
        'is_custom_option_changed', 'has_options', 'required_options', 'force_reindex_eav_required',
        'save_rewrites_history', 'url_key_create_redirect', 'affect_product_custom_options',
    ];

    /**
     * Non-EAV product data that is the same in every store view.
     *
     * Only compared when the product has orig data for the key: ProductRepository fills
     * several of them (e.g. configurable_product_links) on every save without orig data.
     */
    private const GLOBAL_DATA_KEYS = [
        'category_ids', 'website_ids', 'product_links', 'media_gallery', 'options',
        'configurable_product_links', 'configurable_product_options', 'associated_product_ids',
        'bundle_options', 'downloadable_data',
    ];

    /**
     * @var BaseProductObserver
     */
    private $baseProductObserver;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var ProductNextActionProvider
     */
    private $productNextActionProvider;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var IdProviderInterface
     */
    private $idProvider;

    /**
     * @var IndexingEntityRepositoryInterface
     */
    private $indexingEntityRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var ConfigModel
     */
    private $configModel;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var SyncSiteAssignmentAction
     */
    private $syncSiteAssignmentAction;

    /**
     * @param BaseProductObserver $baseProductObserver
     * @param AthosCommerceLogger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductNextActionProvider $productNextActionProvider
     * @param ProductRepositoryInterface $productRepository
     * @param IdProviderInterface $idProvider
     * @param IndexingEntityRepositoryInterface $indexingEntityRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param ConfigModel $configModel
     * @param StoreManagerInterface|null $storeManager
     * @param SyncSiteAssignmentAction|null $syncSiteAssignmentAction
     */
    public function __construct(
        BaseProductObserver $baseProductObserver,
        AthosCommerceLogger $logger,
        ScopeConfigInterface $scopeConfig,
        ProductNextActionProvider $productNextActionProvider,
        ProductRepositoryInterface $productRepository,
        IdProviderInterface $idProvider,
        IndexingEntityRepositoryInterface $indexingEntityRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        ConfigModel $configModel,
        ?StoreManagerInterface $storeManager = null,
        ?SyncSiteAssignmentAction $syncSiteAssignmentAction = null
    )
    {
        $this->baseProductObserver = $baseProductObserver;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->productNextActionProvider = $productNextActionProvider;
        $this->productRepository = $productRepository;
        $this->idProvider = $idProvider;
        $this->indexingEntityRepository = $indexingEntityRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->configModel = $configModel;
        $this->storeManager = $storeManager
            ?? ObjectManager::getInstance()->get(StoreManagerInterface::class);
        $this->syncSiteAssignmentAction = $syncSiteAssignmentAction
            ?? ObjectManager::getInstance()->get(SyncSiteAssignmentAction::class);
    }

    /**
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            $event = $observer->getEvent();
            $product = $event->getProduct();
            $storeIds = method_exists($product, 'getStoreIds') ? $product->getStoreIds() : [];

            if (!$product || !$product->getId()) {
                return;
            }
            $storeIds = $this->getAffectedStoreIds($product, $storeIds);
            // Brand-new products get their rows from discovery; a product that is already indexed
            // and was added to another website needs rows for that website's site now.
            $isIndexed = $this->syncSiteAssignmentAction->hasRows((int)$product->getId());

            foreach ($storeIds as $storeId) {
                try {

                    $liveIndexing = (bool)$this->scopeConfig->getValue(
                        Constants::XML_PATH_LIVE_INDEXING_ENABLED,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        $storeId
                    );

                    if (!$liveIndexing) {
                        continue;
                    }

                    // The saved object holds the values of the scope it was saved in; resolve
                    // status/visibility for this store view so other sites are not affected.
                    $nextAction = $this->productNextActionProvider->getNextActionByProductForStore(
                        $product,
                        (int)$storeId
                    );
                    $siteId = $this->resolveSiteIdByStoreId((int)$storeId);
                    if ($isIndexed && $siteId !== null && $nextAction === Actions::UPSERT) {
                        $this->syncSiteAssignmentAction->addMissingRows([(int)$product->getId()], $siteId);
                    }

                    $this->baseProductObserver->execute(
                        [$product->getId()],
                        $nextAction,
                        true,
                        $siteId !== null ? [$siteId] : []
                    );
                    $this->updateParentEntities($product, (int)$storeId, $siteId);

                    $this->logger->debug(
                        '[UpdateObserver] executed',
                        [
                            'id(s)' => $product->getId(),
                            'store_id' => $storeId,
                            'nextAction' => $nextAction,
                        ]
                    );
                } catch (\Throwable $storeEx) {
                    $this->logger->error(
                        '[UpdateObserver] error for store',
                        [
                            'product_id' => $product->getId(),
                            'store_id' => $storeId,
                            'message' => $storeEx->getMessage(),
                            'trace' => $storeEx->getTraceAsString()
                        ]
                    );
                }
            }
            // Websites removed in this save: their sites no longer serve the product.
            $this->syncSiteAssignmentAction->queueDeleteForUnassignedSites([(int)$product->getId()]);
        } catch (\Throwable $e) {
            $this->logger->error(
                '[UpdateObserver] error: ' . $e->getMessage(),
                [
                    'product_id' => $product->getId() ?? null,
                    'store_ids' => $storeIds ?? [],
                    'trace' => $e->getTraceAsString()
                ]
            );
        }
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param int $storeId
     * @param string|null $siteId
     * @return void
     */
    private function updateParentEntities(
        \Magento\Catalog\Api\Data\ProductInterface $product,
        int $storeId,
        ?string $siteId
    ): void {
        $parentId = (int)$this->idProvider->getItemParentId($product);
        $productId = (int)$product->getId();

        if ($parentId <= 0 || $parentId === $productId) {
            return;
        }

        $parentProduct = $this->productRepository->getById($parentId, false, $storeId, true);
        $nextAction = $this->productNextActionProvider->getNextActionByProduct($parentProduct, $storeId);
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create()
            ->addFilter(IndexingEntity::TARGET_ID, $parentId);

        if ($siteId !== null) {
            $searchCriteriaBuilder->addFilter(IndexingEntity::SITE_ID, $siteId);
        }

        $searchCriteria = $searchCriteriaBuilder->create();

        foreach ($this->indexingEntityRepository->getList($searchCriteria)->getItems() as $indexingEntity) {
            if ($indexingEntity->getTargetParentId() !== null) {
                continue;
            }

            if ($nextAction === Actions::UPSERT) {
                $indexingEntity->setNextAction(Actions::UPSERT);
                $indexingEntity->setIsIndexable(true);
                $this->indexingEntityRepository->save($indexingEntity);
                continue;
            }

            if ($indexingEntity->getLastAction() === Actions::NO_ACTION) {
                $indexingEntity->setNextAction(Actions::NO_ACTION);
                $indexingEntity->setIsIndexable(false);
            } else {
                $indexingEntity->setNextAction(Actions::DELETE);
            }

            $this->indexingEntityRepository->save($indexingEntity);
        }
    }

    /**
     * Store views whose values this save changed.
     *
     * A save at store-view scope only changes that store view when every changed attribute is
     * store scoped. Magento writes website-scoped attributes (e.g. status) to every store view of
     * the website, and global attributes and data (e.g. price with global price scope, categories)
     * to all store views. A save at default scope affects every store of the product.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param array $storeIds
     * @return int[]
     */
    private function getAffectedStoreIds(
        \Magento\Catalog\Api\Data\ProductInterface $product,
        array $storeIds
    ): array {
        $storeIds = array_map('intval', $storeIds);
        $savedStoreId = (int)$product->getStoreId();
        if ($savedStoreId === Store::DEFAULT_STORE_ID || !in_array($savedStoreId, $storeIds, true)) {
            return $storeIds;
        }
        $scope = $this->getChangeScope($product);
        if ($scope === self::SCOPE_GLOBAL) {
            return $storeIds;
        }
        if ($scope === self::SCOPE_STORE) {
            return [$savedStoreId];
        }

        $websiteId = (int)$this->storeManager->getStore($savedStoreId)->getWebsiteId();

        return array_values(array_filter(
            $storeIds,
            fn (int $storeId): bool => (int)$this->storeManager->getStore($storeId)->getWebsiteId() === $websiteId
        ));
    }

    /**
     * Widest scope among the attributes this save changed.
     *
     * Orig data is still the loaded state during catalog_product_save_after.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return int
     */
    private function getChangeScope(\Magento\Catalog\Api\Data\ProductInterface $product): int
    {
        if (!$product instanceof \Magento\Catalog\Model\Product) {
            return self::SCOPE_GLOBAL;
        }
        $resource = $product->getResource();
        $scope = self::SCOPE_STORE;
        foreach (array_keys($product->getData()) as $code) {
            if (!is_string($code)
                || in_array($code, self::IGNORED_CHANGE_KEYS, true)
                || !$product->dataHasChangedFor($code)
            ) {
                continue;
            }
            if (in_array($code, self::GLOBAL_DATA_KEYS, true)) {
                if (array_key_exists($code, (array)$product->getOrigData())) {
                    return self::SCOPE_GLOBAL;
                }
                continue;
            }
            $attribute = $resource->getAttribute($code);
            if (!$attribute || !method_exists($attribute, 'isScopeStore') || $attribute->isScopeStore()) {
                continue;
            }
            if (!$attribute->isScopeWebsite()) {
                return self::SCOPE_GLOBAL;
            }
            $scope = self::SCOPE_WEBSITE;
        }

        return $scope;
    }

    /**
     * @param int $storeId
     * @return string|null
     */
    private function resolveSiteIdByStoreId(int $storeId): ?string
    {
        $siteId = trim($this->configModel->getSiteIdByStoreId($storeId));

        return $siteId !== '' ? $siteId : null;
    }
}
