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

namespace AthosCommerce\Feed\Test\Integration\Model;

use AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface;
use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Model\EntityDiscovery;
use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Test\Integration\Traits\IndexingEntitiesTrait;
use AthosCommerce\Feed\Test\Integration\Traits\ParentRelationFixturesTrait;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Discovery must store the parent ENTITY id in target_parent_id. On Commerce (staging) the relation
 * tables hold the parent row_id, which previously leaked into target_parent_id.
 *
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 * @covers \AthosCommerce\Feed\Model\EntityDiscovery
 */
class EntityDiscoveryParentMappingTest extends TestCase
{
    use IndexingEntitiesTrait;
    use ParentRelationFixturesTrait;

    private const SITE_ID = 'test-disc-parent';

    private ?ObjectManagerInterface $objectManager = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->cleanIndexingEntities(self::SITE_ID);
        $this->forceRowIdEntityIdDivergence();

        /** @var MutableScopeConfigInterface $config */
        $config = $this->objectManager->get(MutableScopeConfigInterface::class);
        foreach ([
            Constants::XML_PATH_CONFIG_ENDPOINT => 'https://athos.test/endpoint',
            Constants::XML_PATH_CONFIG_SITE_ID => self::SITE_ID,
            Constants::XML_PATH_LIVE_INDEXING_ENABLED => '1',
            Constants::XML_PATH_LIVE_INDEXING_TASK_PAYLOAD => '{"includeOutOfStock":true}',
        ] as $path => $value) {
            $config->setValue($path, $value, ScopeInterface::SCOPE_STORE, 'default');
        }
    }

    protected function tearDown(): void
    {
        $this->cleanIndexingEntities(self::SITE_ID);
        parent::tearDown();
    }

    /**
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_attribute.php
     */
    public function testExecute_ConfigurableChildren_StoreParentEntityId(): void
    {
        [$parent, $children] = $this->createConfigurableWithChildren();
        $parentId = (int)$parent->getId();
        $this->assertLinkFieldDiffersFromEntityId($parentId);
        $this->reindexPrices(array_merge([$parentId], array_map(fn($child) => (int)$child->getId(), $children)));

        $this->objectManager->get(EntityDiscovery::class)->execute(['default']);

        foreach ($children as $child) {
            $entity = $this->getIndexingEntityByTargetId((int)$child->getId());
            $this->assertNotNull($entity, 'No indexing entity discovered for child ' . $child->getSku());
            $this->assertSame($parentId, $entity->getTargetParentId(), 'target_parent_id must be parent entity_id');
        }
    }

    public function testExecute_GroupedChild_StoresParentEntityId(): void
    {
        [$parent, $child] = $this->createGroupedWithChild();
        $parentId = (int)$parent->getId();
        $this->assertLinkFieldDiffersFromEntityId($parentId);
        $this->reindexPrices([$parentId, (int)$child->getId()]);

        $this->objectManager->get(EntityDiscovery::class)->execute(['default']);

        $entity = $this->getIndexingEntityByTargetId((int)$child->getId());
        $this->assertNotNull($entity, 'No indexing entity discovered for grouped child');
        $this->assertSame($parentId, $entity->getTargetParentId(), 'target_parent_id must be parent entity_id');
    }

    private function assertLinkFieldDiffersFromEntityId(int $entityId): void
    {
        if ($this->getProductLinkField() === 'entity_id') {
            return;
        }

        $this->assertNotSame(
            $entityId,
            $this->getProductLinkFieldValue($entityId),
            'Fixture precondition: parent row_id must differ from entity_id'
        );
    }

    private function getIndexingEntityByTargetId(int $targetId): ?IndexingEntity
    {
        $searchCriteria = $this->objectManager->get(SearchCriteriaBuilderFactory::class)->create()
            ->addFilter(IndexingEntity::TARGET_ID, $targetId)
            ->addFilter(IndexingEntity::SITE_ID, self::SITE_ID)
            ->create();
        $items = $this->objectManager->get(IndexingEntityRepositoryInterface::class)
            ->getList($searchCriteria)
            ->getItems();

        return $items ? reset($items) : null;
    }
}
