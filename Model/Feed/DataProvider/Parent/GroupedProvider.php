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

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Parent;

use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant as ParentConstant;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionProvider;
use Magento\Eav\Model\Entity;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use \Magento\GroupedProduct\Model\ResourceModel\Product\Link as MagentoGroupedProductLink;
use Psr\Log\LoggerInterface;

class GroupedProvider
{
    /**
     * @var ProductResourceModel
     */
    private $productResourceModel;
    /**
     * @var MetadataPool
     */
    private $metadataPool;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ProductResourceModel $productResourceModel,
        MetadataPool $metadataPool,
        ResourceConnection $resourceConnection,
        ProductCollectionFactory $productCollectionFactory,
        LoggerInterface $logger,
    )
    {
        $this->productResourceModel = $productResourceModel;
        $this->metadataPool = $metadataPool;
        $this->resourceConnection = $resourceConnection;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * @param array $childIds
     *
     * @return array
     */
    public function getParentProductRelations(array $childIds)
    {
        $childIds = array_values(array_unique(array_map('intval', $childIds)));
        if (!$childIds) {
            return [];
        }

        $connection = $this->productResourceModel->getConnection();
        $select = $connection->select();
        $productLinkTable = $this->resourceConnection->getTableName('catalog_product_link');
        $productEntityTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $select->from(
            [ParentConstant::CATALOG_PRODUCT_LINK => $productLinkTable],
            []
        );
        $select->join(
            [ParentConstant::PARENT_CATALOG_PRODUCT_ENTITY_ALIAS => $productEntityTable],
            sprintf(
                '%s.%s = %s.product_id',
                ParentConstant::PARENT_CATALOG_PRODUCT_ENTITY_ALIAS,
                $connection->quoteIdentifier(
                    $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField()
                ),
                ParentConstant::CATALOG_PRODUCT_LINK
            ),
            [ParentConstant::PARENT_CATALOG_PRODUCT_ENTITY_ALIAS . '.' . Entity::DEFAULT_ENTITY_ID_FIELD]
        );
        $select->where(
            ParentConstant::CATALOG_PRODUCT_LINK . '.linked_product_id IN (?)',
            $childIds
        );
        $select->where(
            ParentConstant::CATALOG_PRODUCT_LINK . '.link_type_id = (?)',
            (int)MagentoGroupedProductLink::LINK_TYPE_GROUPED
        );
        $select->reset('columns');
        $select->columns([
            ParentConstant::CATALOG_PRODUCT_LINK . '.linked_product_id AS product_id',
            ParentConstant::CATALOG_PRODUCT_LINK . '.product_id AS parent_id',
        ]);
        $relations = $connection->fetchAll($select);
        unset($connection, $select);

        return $relations;
    }
}
