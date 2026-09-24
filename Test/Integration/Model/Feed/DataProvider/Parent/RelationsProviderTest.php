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

namespace AthosCommerce\Feed\Test\Integration\Model\Feed\DataProvider\Parent;

use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\RelationsProvider;
use AthosCommerce\Feed\Test\Integration\Traits\ParentRelationFixturesTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Relations must expose both the parent link field (parent_id, row_id on Commerce) used by the
 * feed data providers and the parent entity_id (parent_entity_id) used for indexing entities.
 *
 * @magentoDbIsolation enabled
 * @covers \AthosCommerce\Feed\Model\Feed\DataProvider\Parent\RelationsProvider
 */
class RelationsProviderTest extends TestCase
{
    use ParentRelationFixturesTrait;

    private ?ObjectManagerInterface $objectManager = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->forceRowIdEntityIdDivergence();
    }

    /**
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_attribute.php
     */
    public function testGetConfigurableRelationIds_ReturnsParentLinkFieldAndEntityId(): void
    {
        [$parent, $children] = $this->createConfigurableWithChildren();
        $parentId = (int)$parent->getId();
        $childIds = array_map(fn($child) => (int)$child->getId(), $children);

        $relations = $this->objectManager->get(RelationsProvider::class)->getConfigurableRelationIds($childIds);

        $this->assertCount(count($childIds), $relations);
        foreach ($relations as $relation) {
            $this->assertContains((int)$relation['product_id'], $childIds);
            $this->assertSame($parentId, (int)$relation['parent_entity_id'], 'parent_entity_id mismatch');
            $this->assertSame(
                $this->getProductLinkFieldValue($parentId),
                (int)$relation['parent_id'],
                'parent_id must stay the parent link field'
            );
        }
    }

    public function testGetGroupRelationIds_ReturnsParentLinkFieldAndEntityId(): void
    {
        [$parent, $child] = $this->createGroupedWithChild();
        $parentId = (int)$parent->getId();

        $relations = $this->objectManager->get(RelationsProvider::class)
            ->getGroupRelationIds([(int)$child->getId()]);

        $this->assertCount(1, $relations);
        $relation = reset($relations);
        $this->assertSame((int)$child->getId(), (int)$relation['product_id']);
        $this->assertSame($parentId, (int)$relation['parent_entity_id'], 'parent_entity_id mismatch');
        $this->assertSame(
            $this->getProductLinkFieldValue($parentId),
            (int)$relation['parent_id'],
            'parent_id must stay the parent link field'
        );
    }
}
