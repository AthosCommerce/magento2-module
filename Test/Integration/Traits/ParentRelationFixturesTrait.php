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

namespace AthosCommerce\Feed\Test\Integration\Traits;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductLinkInterface;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as ConfigurableOptionsFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Configurable / grouped fixtures whose parent row_id differs from its entity_id.
 *
 * Requires $this->objectManager and the Magento/ConfigurableProduct/_files/configurable_attribute.php
 * fixture for configurable products.
 */
trait ParentRelationFixturesTrait
{
    private function getProductLinkField(): string
    {
        return $this->objectManager->get(MetadataPool::class)
            ->getMetadata(ProductInterface::class)
            ->getLinkField();
    }

    /**
     * On Commerce, entity_id comes from sequence_product while row_id is the table's auto-increment.
     * On a fresh test database both counters are equal, which hides row_id/entity_id mix-ups, so
     * move the entity_id sequence far ahead. No-op on Open Source (link field = entity_id).
     */
    private function forceRowIdEntityIdDivergence(): void
    {
        if ($this->getProductLinkField() === 'entity_id') {
            return;
        }

        $resource = $this->objectManager->get(ResourceConnection::class);
        $connection = $resource->getConnection();
        $sequenceTable = $resource->getTableName('sequence_product');
        $connection->insert($sequenceTable, ['sequence_value' => null]);
        $next = (int)$connection->lastInsertId($sequenceTable);
        $connection->insert($sequenceTable, ['sequence_value' => $next + 10000]);
    }

    /**
     * Price reindex on save runs as a commit callback, which never fires under magentoDbIsolation;
     * the discovery collection inner-joins the price index.
     *
     * @param int[] $productIds
     */
    private function reindexPrices(array $productIds): void
    {
        $this->objectManager->get(\Magento\Framework\Indexer\IndexerRegistry::class)
            ->get('catalog_product_price')
            ->reindexList($productIds);
    }

    private function getProductLinkFieldValue(int $entityId): int
    {
        $resource = $this->objectManager->get(ResourceConnection::class);
        $connection = $resource->getConnection();

        return (int)$connection->fetchOne(
            $connection->select()
                ->from($resource->getTableName('catalog_product_entity'), [$this->getProductLinkField()])
                ->where('entity_id = ?', $entityId)
        );
    }

    /**
     * @return array{0: ProductInterface, 1: ProductInterface[]}
     */
    private function createConfigurableWithChildren(int $childCount = 2): array
    {
        $attribute = $this->objectManager->get(EavConfig::class)
            ->getAttribute(Product::ENTITY, 'test_configurable');
        $options = $attribute->getOptions();
        array_shift($options);

        $children = [];
        $values = [];
        foreach (array_slice(array_values($options), 0, $childCount) as $i => $option) {
            $child = $this->buildProduct(Type::TYPE_SIMPLE, 'athos_rel_cfg_child_' . $i, Visibility::VISIBILITY_NOT_VISIBLE);
            $child->setData($attribute->getAttributeCode(), $option->getValue());
            $children[] = $this->productRepository()->save($child);
            $values[] = [
                'label' => 'option ' . $i,
                'attribute_id' => $attribute->getId(),
                'value_index' => $option->getValue(),
            ];
        }

        $configurableOptions = $this->objectManager->get(ConfigurableOptionsFactory::class)->create([[
            'attribute_id' => $attribute->getId(),
            'code' => $attribute->getAttributeCode(),
            'label' => $attribute->getStoreLabel(),
            'position' => '0',
            'values' => $values,
        ]]);

        $parent = $this->buildProduct(Configurable::TYPE_CODE, 'athos_rel_cfg_parent', Visibility::VISIBILITY_BOTH);
        $extension = $parent->getExtensionAttributes();
        $extension->setConfigurableProductOptions($configurableOptions);
        $extension->setConfigurableProductLinks(array_map(fn($child) => (int)$child->getId(), $children));
        $parent->setExtensionAttributes($extension);

        return [$this->productRepository()->save($parent), $children];
    }

    /**
     * @return array{0: ProductInterface, 1: ProductInterface}
     */
    private function createGroupedWithChild(): array
    {
        $child = $this->productRepository()->save(
            $this->buildProduct(Type::TYPE_SIMPLE, 'athos_rel_grp_child', Visibility::VISIBILITY_NOT_VISIBLE)
        );

        $parent = $this->buildProduct(Grouped::TYPE_CODE, 'athos_rel_grp_parent', Visibility::VISIBILITY_BOTH);
        /** @var ProductLinkInterface $link */
        $link = $this->objectManager->get(ProductLinkInterfaceFactory::class)->create();
        $link->setSku($parent->getSku())
            ->setLinkType('associated')
            ->setLinkedProductSku($child->getSku())
            ->setLinkedProductType($child->getTypeId())
            ->getExtensionAttributes()
            ->setQty(1);
        $parent->setProductLinks([$link]);

        return [$this->productRepository()->save($parent), $child];
    }

    private function buildProduct(string $typeId, string $skuPrefix, int $visibility): Product
    {
        /** @var Product $product */
        $product = $this->objectManager->create(Product::class);
        $product->setTypeId($typeId)
            ->setAttributeSetId(4)
            ->setName($skuPrefix . ' ' . uniqid('', true))
            ->setSku($skuPrefix . '_' . uniqid('', true))
            ->setStatus(Status::STATUS_ENABLED)
            ->setVisibility($visibility)
            ->setStockData(['qty' => 10, 'is_in_stock' => 1, 'manage_stock' => 0])
            ->setWebsiteIds([$this->objectManager->get(StoreManagerInterface::class)->getStore()->getWebsiteId()]);

        if ($typeId === Type::TYPE_SIMPLE) {
            $product->setPrice(10);
        }

        return $product;
    }

    private function productRepository(): ProductRepositoryInterface
    {
        return $this->objectManager->get(ProductRepositoryInterface::class);
    }
}
