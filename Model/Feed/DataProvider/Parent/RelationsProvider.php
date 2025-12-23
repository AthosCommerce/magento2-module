<?php

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Parent;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant as ParentConstant;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionProvider;
use Magento\Eav\Model\Entity;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\GroupedProduct\Model\ResourceModel\Product\Link as MagentoGroupedProductLink;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class RelationsProvider
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
     * @var MetadataPool
     */
    private $metadataPool;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param ProductResourceModel $productResourceModel
     * @param OptionProvider $optionProvider
     * @param ResourceConnection $resourceConnection
     * @param ProductCollectionFactory $productCollectionFactory
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        ProductResourceModel $productResourceModel,
        OptionProvider $optionProvider,
        ResourceConnection $resourceConnection,
        ProductCollectionFactory $productCollectionFactory,
        MetadataPool $metadataPool,
        AthosCommerceLogger $logger,
    ) {
        $this->productResourceModel = $productResourceModel;
        $this->optionProvider = $optionProvider;
        $this->resourceConnection = $resourceConnection;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->metadataPool = $metadataPool;
        $this->logger = $logger;
    }

    /**
     * @param array $childIds
     *
     * @return array
     */
    public function getConfigurableRelationIds(array $childIds): array
    {
        $childIds = $this->formatChildIds($childIds);
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

    /**
     * @param array $childIds
     *
     * @return array
     */
    public function getGroupRelationIds(array $childIds): array
    {
        $childIds = $this->formatChildIds($childIds);
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

    /**
     * @param array $childIds
     *
     * @return array
     */
    private function formatChildIds(array $childIds): array
    {
        return array_values(array_unique(array_map('intval', $childIds)));
    }
}
