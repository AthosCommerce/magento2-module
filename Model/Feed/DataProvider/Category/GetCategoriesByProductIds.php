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

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Category;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\CategoryLinkInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;

class GetCategoriesByProductIds
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
     * GetCategoriesByProductIds constructor.
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
     * return
     * [
     *      product_id => [
     *          [
     *              'category_id' => int
     *              'path' => string
     *          ],
     *          .........
     *      ],
     *      ...........
     * ]
     *
     * @param array $ids
     * @return array
     * @throws \Exception
     */
    public function execute(array $ids) : array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select();
        $select->from(
            ['main_table' => $this->metadataPool->getMetadata(CategoryLinkInterface::class)->getEntityTable()],
            ['category_id', 'product_id']
        );
        $select->join(
            ['ct' => $this->metadataPool->getMetadata(CategoryInterface::class)->getEntityTable()],
            'ct.entity_id = main_table.category_id',
            ['path']
        );
        $select->where('product_id IN (?)', $ids);

        $categories = $connection->fetchAll($select);
        $result = [];
        $uniqueCheck = [];
        foreach ($categories as $categoryData) {
            $productId = $categoryData['product_id'] ?? null;
            $categoryId = $categoryData['category_id'] ?? null;
            $path = $categoryData['path'] ?? null;
            if (!$categoryId || !$productId || !$path) {
                continue;
            }

            $uniqueKey = $productId . '_' . $categoryId;
            if (isset($uniqueCheck[$uniqueKey])) {
                continue;
            }

            $result[$productId][] = [
                'category_id' => (int) $categoryId,
                'path' => $path
            ];
            $uniqueCheck[$uniqueKey] = true;
        }

        return $result;
    }
}
