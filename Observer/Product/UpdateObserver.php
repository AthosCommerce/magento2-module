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
use AthosCommerce\Feed\Observer\BaseProductObserver;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Service\Provider\ProductNextActionProvider;

class UpdateObserver implements ObserverInterface
{
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
     * @param BaseProductObserver $baseProductObserver
     * @param AthosCommerceLogger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductNextActionProvider $productNextActionProvider
     * @param ProductRepositoryInterface $productRepository
     * @param IdProviderInterface $idProvider
     * @param IndexingEntityRepositoryInterface $indexingEntityRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param ConfigModel $configModel
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
        ConfigModel $configModel
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

                    $nextAction = $this->productNextActionProvider->getNextActionByProduct($product, (int)$storeId);
                    $siteId = $this->resolveSiteIdByStoreId((int)$storeId);

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
     * @param int $storeId
     * @return string|null
     */
    private function resolveSiteIdByStoreId(int $storeId): ?string
    {
        $siteId = trim($this->configModel->getSiteIdByStoreId($storeId));

        return $siteId !== '' ? $siteId : null;
    }
}
