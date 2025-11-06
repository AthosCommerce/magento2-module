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

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant as ParentConstant;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionProvider;
use Magento\Eav\Model\Entity;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class ConfigurableProvider
{
    /**
     * @var ProductResourceModel
     */
    private $productResourceModel;
    /**
     * @var OptionProvider
     */
    private $optionProvider;
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

    /**
     * @param ProductResourceModel $productResourceModel
     * @param OptionProvider $optionProvider
     * @param ResourceConnection $resourceConnection
     * @param ProductCollectionFactory $productCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductResourceModel $productResourceModel,
        OptionProvider $optionProvider,
        ResourceConnection $resourceConnection,
        ProductCollectionFactory $productCollectionFactory,
        LoggerInterface $logger,
    ) {
        $this->productResourceModel = $productResourceModel;
        $this->optionProvider = $optionProvider;
        $this->resourceConnection = $resourceConnection;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * @param $childIds
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
        $productLinkTable = $this->resourceConnection->getTableName('catalog_product_super_link');
        $productEntityTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $select->from(
            [ParentConstant::CATALOG_PRODUCT_SUPER_LINK_ALIAS => $productLinkTable],
            []
        );
        $select->join(
            [ParentConstant::PARENT_CATALOG_PRODUCT_ENTITY_ALIAS => $productEntityTable],
            sprintf(
                '%s.%s = %s.parent_id',
                ParentConstant::PARENT_CATALOG_PRODUCT_ENTITY_ALIAS,
                $connection->quoteIdentifier($this->optionProvider->getProductEntityLinkField()),
                ParentConstant::CATALOG_PRODUCT_SUPER_LINK_ALIAS
            ),
            [ParentConstant::PARENT_CATALOG_PRODUCT_ENTITY_ALIAS . '.' . Entity::DEFAULT_ENTITY_ID_FIELD]
        );
        $select->where(
            ParentConstant::CATALOG_PRODUCT_SUPER_LINK_ALIAS . '.product_id IN (?)',
            $childIds
        );
        $select->reset('columns');
        $select->columns([
            ParentConstant::PARENT_CATALOG_PRODUCT_ENTITY_ALIAS . '.' . Entity::DEFAULT_ENTITY_ID_FIELD,
            ParentConstant::CATALOG_PRODUCT_SUPER_LINK_ALIAS . '.product_id',
            ParentConstant::CATALOG_PRODUCT_SUPER_LINK_ALIAS . '.parent_id',
        ]);
        $relations = $connection->fetchAll($select);
        unset($connection, $select);

        return $relations;
    }
}
