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

namespace AthosCommerce\Feed\Service;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\GroupedProduct\Model\ResourceModel\Product\Link as GroupedLinkResource;

class GroupedParentIdResolver
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param ResourceConnection $resourceConnection
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
    }

    /**
     * @param int $childId
     * @return array<int, int>
     */
    public function getParentIdsByChildId(int $childId): array
    {
        if ($childId <= 0) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        $productLinkTable = $this->resourceConnection->getTableName('catalog_product_link');
        $productEntityTable = $this->resourceConnection->getTableName('catalog_product_entity');

        $select = $connection->select()
            ->from(['link' => $productLinkTable], [])
            ->join(
                ['parent' => $productEntityTable],
                sprintf('parent.%s = link.product_id', $connection->quoteIdentifier($linkField)),
                []
            )
            ->where('link.linked_product_id = ?', $childId)
            ->where('link.link_type_id = ?', GroupedLinkResource::LINK_TYPE_GROUPED)
            ->columns(['parent.entity_id']);

        return array_values(array_unique(array_map('intval', $connection->fetchCol($select))));
    }
}
