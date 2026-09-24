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

namespace AthosCommerce\Feed\Service\Provider;

use AthosCommerce\Feed\Model\Config as ConfigModel;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Maps store views, websites and Athos site ids for live indexing.
 */
class LiveIndexingSiteProvider
{
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
     * @param StoreManagerInterface $storeManager
     * @param ConfigModel $configModel
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ConfigModel $configModel,
        ResourceConnection $resourceConnection
    ) {
        $this->storeManager = $storeManager;
        $this->configModel = $configModel;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Site id configured for the store view, or '' when none.
     *
     * @param int $storeId
     * @return string
     */
    public function getSiteId(int $storeId): string
    {
        return trim((string)$this->configModel->getSiteIdByStoreId($storeId));
    }

    /**
     * Store views with live indexing enabled and a site id.
     *
     * @return StoreInterface[]
     */
    public function getLiveIndexingStores(): array
    {
        return array_values(array_filter(
            $this->storeManager->getStores(false),
            fn (StoreInterface $store): bool => $this->configModel->isLiveIndexingEnabled((int)$store->getId())
                && $this->getSiteId((int)$store->getId()) !== ''
        ));
    }

    /**
     * Site ids served by each website: every store view with a site id counts.
     *
     * A site id may be shared by store views of several websites; a product stays on the site
     * while it is assigned to any of them.
     *
     * @return array<int, string[]> website id => site ids
     */
    public function getSiteIdsByWebsite(): array
    {
        $result = [];
        foreach ($this->storeManager->getStores(false) as $store) {
            $siteId = $this->getSiteId((int)$store->getId());
            if ($siteId !== '') {
                $result[(int)$store->getWebsiteId()][$siteId] = $siteId;
            }
        }

        return array_map('array_values', $result);
    }

    /**
     * Websites whose store views send to this site id.
     *
     * @param string $siteId
     * @return int[]
     */
    public function getWebsiteIdsForSite(string $siteId): array
    {
        $siteId = trim($siteId);
        $websiteIds = [];
        foreach ($this->getSiteIdsByWebsite() as $websiteId => $siteIds) {
            if (in_array($siteId, $siteIds, true)) {
                $websiteIds[] = (int)$websiteId;
            }
        }

        return $websiteIds;
    }

    /**
     * Site ids the products are currently served on, from their website assignments.
     *
     * @param int[] $productIds
     * @return array<int, string[]> product id => site ids
     */
    public function getServedSiteIds(array $productIds): array
    {
        if (!$productIds) {
            return [];
        }
        $siteIdsByWebsite = $this->getSiteIdsByWebsite();
        $connection = $this->resourceConnection->getConnection();
        $rows = $connection->fetchAll(
            $connection->select()
                ->from(
                    $this->resourceConnection->getTableName('catalog_product_website'),
                    ['product_id', 'website_id']
                )
                ->where('product_id IN (?)', $productIds)
        );
        $result = [];
        foreach ($rows as $row) {
            foreach ($siteIdsByWebsite[(int)$row['website_id']] ?? [] as $siteId) {
                $result[(int)$row['product_id']][$siteId] = $siteId;
            }
        }

        return array_map('array_values', $result);
    }
}
