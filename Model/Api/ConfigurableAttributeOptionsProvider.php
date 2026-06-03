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

use AthosCommerce\Feed\Api\ConfigurableAttributeOptionsProviderInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Eav\Model\Entity\TypeFactory;
use AthosCommerce\Feed\Service\Provider\StoreProvider;

class ConfigurableAttributeOptionsProvider implements ConfigurableAttributeOptionsProviderInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var CollectionFactory
     */
    private $attributeCollectionFactory;
    /**
     * @var StoreProvider
     */
    private $storeProvider;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CollectionFactory $attributeCollectionFactory
     * @param StoreProvider $storeProvider
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        ResourceConnection  $resourceConnection,
        CollectionFactory   $attributeCollectionFactory,
        StoreProvider       $storeProvider,
        AthosCommerceLogger $logger
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->storeProvider = $storeProvider;
        $this->logger = $logger;
    }

    /**
     * @param string|null $storeCode
     * @return array|string[]
     */
    public function getOptions(?string $storeCode = null): array
    {
        $storeId = $this->storeProvider->getStoreId($storeCode);

        $attributeIds = $this->getConfigurableAttributeIds();

        $this->logger->debug(
            'ConfigurableAttributeIds',
            [
                'storeCode' => $storeCode,
                'storeId' => $storeId,
                'attributeIds' => $attributeIds
            ]
        );

        if (!$attributeIds) {
            $this->logger->info('No configurable attribute ids found');
            return [];
        }

        $collection = $this->attributeCollectionFactory->create();
        $collection->addStoreLabel($storeId);

        $collection->addFieldToFilter(
            'main_table.attribute_id',
            ['in' => $attributeIds]
        );

        $labels = [];
        $this->logger->debug(
            'ConfigurableAttributeCollection',
            [
                'storeId' => $storeId,
                'attributeIds' => $attributeIds,
                'query' => $collection->getSelect()->__toString()
            ]
        );
        foreach ($collection as $attribute) {
            if (!$attribute) {
                continue;
            }

            $label = trim((string)$attribute->getStoreLabel());

            if ($label === '') {
                continue;
            }

            $labels[$label] = $label;
        }

        natcasesort($labels);

        return array_values($labels);
    }

    /**
     * @return int[]
     */
    private function getConfigurableAttributeIds(): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->distinct()
            ->from(
                $this->resourceConnection->getTableName('catalog_product_super_attribute'),
                ['attribute_id']
            );

        return array_map(
            'intval',
            $connection->fetchCol($select)
        );
    }
}
