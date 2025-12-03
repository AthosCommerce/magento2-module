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

namespace AthosCommerce\Feed\Test\Integration\Model;

use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Model\ResourceModel\IndexingEntity as IndexingEntityResourceModel;
use AthosCommerce\Feed\Model\ResourceModel\IndexingEntity\Collection as IndexingEntityCollection;
use AthosCommerce\Feed\Api\Data\IndexingEntityInterface;
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Test\Integration\Traits\ObjectInstantiationTrait;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 * @method IndexingEntityInterface instantiateTestObject(?array $arguments = null)
 * @method IndexingEntityInterface instantiateTestObjectFromInterface(?array $arguments = null)
 */
class IndexingEntityTest extends TestCase
{
    use ObjectInstantiationTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null; // @phpstan-ignore-line

    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = \AthosCommerce\Feed\Model\IndexingEntity::class;
        $this->interfaceFqcn = \AthosCommerce\Feed\Api\Data\IndexingEntityInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testCanSaveAndLoad(): void
    {
        $indexingEntity = $this->createIndexingEntity();
        /** @var AbstractModel $indexingEntityToLoad */
        $indexingEntityToLoad = $this->instantiateTestObject();
        $resourceModel = $this->instantiateSyncResourceModel();
        $resourceModel->load(
            $indexingEntityToLoad,
            $indexingEntity->getId(),
        );

        $this->assertSame(
            (int)$indexingEntity->getId(),
            $indexingEntityToLoad->getId(),
        );
        $this->assertSame(
            '__PRODUCT',
            $indexingEntityToLoad->getTargetEntityType(),
        );
        $this->assertSame(
            $indexingEntity->getTargetEntityType(),
            $indexingEntityToLoad->getTargetEntityType(),
        );
        $this->assertNull(
            $indexingEntityToLoad->getTargetEntitySubtype(),
        );
        $this->assertSame(
            $indexingEntity->getTargetEntitySubtype(),
            $indexingEntityToLoad->getTargetEntitySubtype(),
        );
        $this->assertSame(
            1,
            $indexingEntity->getTargetId(),
        );
        $this->assertSame(
            $indexingEntity->getTargetId(),
            $indexingEntityToLoad->getTargetId(),
        );
        $this->assertSame(
            'Upsert',
            $indexingEntityToLoad->getNextAction(),
        );
        $this->assertSame(
            $indexingEntity->getNextAction(),
            $indexingEntityToLoad->getNextAction(),
        );
        $this->assertNull(
            $indexingEntity->getLockTimestamp(),
        );
        $this->assertSame(
            $indexingEntity->getLockTimestamp(),
            $indexingEntityToLoad->getLockTimestamp(),
        );
        $this->assertSame(
            'Upsert',
            $indexingEntityToLoad->getLastAction(),
        );
        $this->assertSame(
            $indexingEntity->getLastAction(),
            $indexingEntityToLoad->getLastAction(),
        );
        $this->assertNull(
            $indexingEntity->getLastActionTimestamp(),
        );
        $this->assertSame(
            $indexingEntity->getLastActionTimestamp(),
            $indexingEntityToLoad->getLastActionTimestamp(),
        );
        $this->assertTrue(
            $indexingEntity->getIsIndexable(),
            'Is Indexable',
        );
        $this->assertSame(
            $indexingEntity->getIsIndexable(),
            $indexingEntityToLoad->getIsIndexable(),
        );
    }

    public function testCanSaveAndLoad_WithTimestamps(): void
    {
        $indexingEntity = $this->createIndexingEntity([
            'target_id' => 100,
            'target_parent_id' => 500,
            'target_entity_subtype' => 'virtual',
            'lock_timestamp' => date('Y-m-d H:i:s', time()),
            'last_action_timestamp' => date('Y-m-d H:i:s', time() - 3600),
        ]);
        /** @var AbstractModel $indexingEntityToLoad */
        $indexingEntityToLoad = $this->instantiateTestObject();
        $resourceModel = $this->instantiateSyncResourceModel();
        $resourceModel->load(
            $indexingEntityToLoad,
            $indexingEntity->getId(),
        );

        $this->assertSame(
            (int)$indexingEntity->getId(),
            $indexingEntityToLoad->getId(),
        );
        $this->assertSame(
            '__PRODUCT',
            $indexingEntity->getTargetEntityType(),
        );
        $this->assertSame(
            $indexingEntity->getTargetEntityType(),
            $indexingEntityToLoad->getTargetEntityType(),
        );
        $this->assertSame(
            'virtual',
            $indexingEntity->getTargetEntitySubtype(),
        );
        $this->assertSame(
            $indexingEntity->getTargetEntitySubtype(),
            $indexingEntityToLoad->getTargetEntitySubtype(),
        );
        $this->assertSame(
            100,
            $indexingEntity->getTargetId(),
        );
        $this->assertSame(
            $indexingEntity->getTargetId(),
            $indexingEntityToLoad->getTargetId(),
        );
        $this->assertSame(
            500,
            $indexingEntity->getTargetParentId(),
        );
        $this->assertSame(
            $indexingEntity->getTargetParentId(),
            $indexingEntityToLoad->getTargetParentId(),
        );
        $this->assertStringContainsString(
            'site-id',
            $indexingEntity->getSiteId(),
        );
        $this->assertSame(
            $indexingEntity->getSiteId(),
            $indexingEntityToLoad->getSiteId(),
        );
        $this->assertSame(
            'Upsert',
            $indexingEntityToLoad->getNextAction(),
        );
        $this->assertSame(
            $indexingEntity->getNextAction(),
            $indexingEntityToLoad->getNextAction(),
        );
        $this->assertNotNull(
            $indexingEntity->getLockTimestamp(),
        );
        $this->assertSame(
            $indexingEntity->getLockTimestamp(),
            $indexingEntityToLoad->getLockTimestamp(),
        );
        $this->assertSame(
            'Upsert',
            $indexingEntityToLoad->getLastAction(),
        );
        $this->assertSame(
            $indexingEntity->getLastAction(),
            $indexingEntityToLoad->getLastAction(),
        );
        $this->assertNotNull($indexingEntity->getLastActionTimestamp());
        $this->assertSame(
            $indexingEntity->getLastActionTimestamp(),
            $indexingEntityToLoad->getLastActionTimestamp(),
        );
        $this->assertTrue(
            $indexingEntity->getIsIndexable(),
        );
        $this->assertSame(
            $indexingEntity->getIsIndexable(),
            $indexingEntityToLoad->getIsIndexable(),
        );
    }

    public function testCanLoadMultipleIndexingEntities(): void
    {
        $indexingEntityA = $this->createIndexingEntity();
        $indexingEntityB = $this->createIndexingEntity([
            'target_entity_type' => '__PRODUCT',
            'target_entity_subtype' => null,
            'target_id' => 2,
            'target_parent_id' => 3,
            'next_action' => Actions::UPSERT,
            'lock_timestamp' => date('Y-m-d H:i:s', time()),
            'last_action' => Actions::NO_ACTION,
            'last_action_timestamp' => date('Y-m-d H:i:s', time() - 3600),
        ]);

        $collection = $this->objectManager->get( IndexingEntityCollection::class);
        $items = $collection->getItems();
        $this->assertContains(
            (int)$indexingEntityA->getId(),
            array_keys($items),
        );
        $this->assertContains(
            (int)$indexingEntityB->getId(),
            array_keys($items),
        );
    }

    /**
     * @param mixed[] $data
     *
     * @return AbstractModel|\AthosCommerce\Feed\Api\Data\IndexingEntityInterface
     * @throws AlreadyExistsException
     */
    private function createIndexingEntity(array $data = []): AbstractModel
    {
        $indexingEntity = $this->instantiateTestObject([]);
        $indexingEntity->setTargetEntityType($data['target_entity_type'] ?? '__PRODUCT');
        $indexingEntity->setTargetEntitySubtype($data['target_entity_subtype'] ?? null);
        $indexingEntity->setTargetId($data['target_id'] ?? 1);
        $indexingEntity->setTargetParentId($data['target_parent_id'] ?? null);
        $indexingEntity->setSiteId(
            $data['site_id'] ?? 'site-id' . random_int(0, 99999),
        );
        $indexingEntity->setNextAction(
            $data['next_action'] ??
            \AthosCommerce\Feed\Model\Source\Actions::UPSERT
        );
        $indexingEntity->setLockTimestamp($data['lock_timestamp'] ?? null);
        $indexingEntity->setLastAction($data['last_action'] ?? Actions::UPSERT);
        $indexingEntity->setLastActionTimestamp($data['last_action_timestamp'] ?? null);
        $indexingEntity->setIsIndexable($data['is_indexable'] ?? true);

        $resourceModel = $this->instantiateSyncResourceModel();
        /** @var AbstractModel $indexingEntity */
        $resourceModel->save($indexingEntity);

        return $indexingEntity;
    }

    /**
     * @return \AthosCommerce\Feed\Model\ResourceModel\IndexingEntity
     */
    private function instantiateSyncResourceModel(): IndexingEntityResourceModel
    {
        return $this->objectManager->get(IndexingEntityResourceModel::class);
    }
}
