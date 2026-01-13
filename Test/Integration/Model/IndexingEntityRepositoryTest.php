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

use AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface;
use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Model\ResourceModel\IndexingEntity as IndexingEntityResourceModel;
use AthosCommerce\Feed\Model\ResourceModel\IndexingEntity\Collection as IndexingEntityCollection;
use AthosCommerce\Feed\Service\Provider\IndexingEntityProvider;
use AthosCommerce\Feed\Api\Data\IndexingEntityInterface;
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Test\Integration\Traits\IndexingEntitiesTrait;
use AthosCommerce\Feed\Test\Integration\Traits\ObjectInstantiationTrait;
use AthosCommerce\Feed\Model\IndexingEntityRepository;
use AthosCommerce\Feed\Exception\CouldNotDeleteException;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

/**
 * @magentoDbIsolation enabled
 * @covers \AthosCommerce\Feed\Model\IndexingEntityRepository::class
 * @method IndexingEntityRepositoryInterface instantiateTestObject(?array $arguments = null)
 * @method IndexingEntityRepositoryInterface instantiateTestObjectFromInterface(?array $arguments = null)
 */
class IndexingEntityRepositoryTest extends TestCase
{
    use ObjectInstantiationTrait;
    use IndexingEntitiesTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null; // @phpstan-ignore-line

    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = IndexingEntityRepository::class;
        $this->interfaceFqcn = IndexingEntityRepositoryInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();

        $this->cleanIndexingEntities('site-id%');
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->cleanIndexingEntities('site-id%');
    }

    public function testCreate_ReturnsIndexingEntityModel(): void
    {
        $repository = $this->instantiateTestObject();
        $indexingEntity = $repository->create();

        $this->assertInstanceOf(
            IndexingEntityInterface::class,
            $indexingEntity,
        );
    }

    public function testGetById_NotExists(): void
    {
        $indexingEntityId = 999999999;

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage(
            sprintf('No such entity with entity_id = %s', $indexingEntityId),
        );

        $repository = $this->instantiateTestObject();
        $repository->getById($indexingEntityId);
    }

    public function testGetById_Exists(): void
    {
        $indexingEntity = $this->createIndexingEntity([
            'target_parent_id' => 123,
        ]);

        $repository = $this->instantiateTestObject();
        $loadedIndexingEntity = $repository->getById((int)$indexingEntity->getId());

        $this->assertSame(
            (int)$indexingEntity->getId(),
            $loadedIndexingEntity->getId(),
            "getId",
        );
        $this->assertSame(
            (int)$indexingEntity->getId(),
            $loadedIndexingEntity->getData(\AthosCommerce\Feed\Model\IndexingEntity::ENTITY_ID),
            "getData('entity_id')",
        );
        $this->assertSame(
            '__PRODUCT',
            $loadedIndexingEntity->getTargetEntityType(),
            "getTargetEntityType",
        );
        $this->assertSame(
            '__PRODUCT',
            $loadedIndexingEntity->getData(IndexingEntity::TARGET_ENTITY_TYPE),
            "getData('target_entity_type')",
        );
        $this->assertNull(
            $loadedIndexingEntity->getTargetEntitySubtype(),
            "getTargetEntityType",
        );
        $this->assertNull(
            $loadedIndexingEntity->getData(IndexingEntity::TARGET_ENTITY_SUBTYPE),
            "getData('target_entity_subtype')",
        );
        $this->assertSame(
            1,
            $loadedIndexingEntity->getTargetId(),
            "getTargetId",
        );
        $this->assertSame(
            1,
            $loadedIndexingEntity->getData(IndexingEntity::TARGET_ID),
            "getData('target_id')",
        );
        $this->assertSame(
            123,
            $loadedIndexingEntity->getTargetParentId(),
            "getTargetParentId",
        );
        $this->assertSame(
            123,
            $loadedIndexingEntity->getData(IndexingEntity::TARGET_PARENT_ID),
            "getData('target_parent_id')",
        );
        $this->assertStringContainsString(
            'site-id-',
            $loadedIndexingEntity->getSiteId(),
            "getSiteId",
        );
        $this->assertStringContainsString(
            'site-id-',
            $loadedIndexingEntity->getData(IndexingEntity::SITE_ID),
            "getData('site_id')",
        );
        $this->assertSame(
            Actions::UPSERT,
            $loadedIndexingEntity->getNextAction(),
            "getNextAction",
        );
        $this->assertSame(
            Actions::UPSERT,
            $loadedIndexingEntity->getData(IndexingEntity::NEXT_ACTION),
            "getData('next_action')",
        );
        $this->assertNull(
            $loadedIndexingEntity->getLockTimestamp(),
            "getLockTimestamp",
        );
        $this->assertNull(
            $loadedIndexingEntity->getData(IndexingEntity::LOCK_TIMESTAMP),
            "getData('lock_timestamp')",
        );
        $this->assertSame(
            Actions::UPSERT,
            $loadedIndexingEntity->getLastAction(),
            "getLastAction",
        );
        $this->assertSame(
            Actions::UPSERT,
            $loadedIndexingEntity->getData(IndexingEntity::LAST_ACTION),
            "getData('last_action')",
        );
        $this->assertNull(
            $loadedIndexingEntity->getLastActionTimestamp(),
            "getLastActionTimestamp",
        );
        $this->assertNull(
            $loadedIndexingEntity->getData(IndexingEntity::LAST_ACTION_TIMESTAMP),
            "getData('last_action_timestamp')",
        );
        $this->assertTrue(
            $loadedIndexingEntity->getIsIndexable(),
            "getIsIndexable",
        );
        $this->assertTrue(
            $loadedIndexingEntity->getData(IndexingEntity::IS_INDEXABLE),
            "getData('is_indexable')",
        );
    }

    public function testSave_Create_Empty(): void
    {
        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessageMatches('#Could not save Indexing Entity: .*#');

        $repository = $this->instantiateTestObject();
        $indexingEntity = $repository->create();
        $repository->save($indexingEntity);
    }

    public function testSave_Create(): void
    {
        $repository = $this->instantiateTestObject();
        $indexingEntity = $repository->create();
        $indexingEntity->setTargetId(1);
        $indexingEntity->setTargetParentId(123);
        $indexingEntity->setTargetEntityType('__PRODUCT');
        $indexingEntity->setTargetEntitySubtype('simple');
        $indexingEntity->setSiteId('site-id-1234');
        $indexingEntity->setLastAction(Actions::NO_ACTION);
        $indexingEntity->setLastActionTimestamp(null);
        $indexingEntity->setNextAction(Actions::UPSERT);
        $indexingEntity->setLockTimestamp(null);
        $indexingEntity->setIsIndexable(true);
        $savedIndexingEntity = $repository->save($indexingEntity);

        $this->assertNotNull($savedIndexingEntity->getId());
    }

    public function testSave_Update(): void
    {
        $repository = $this->instantiateTestObject();
        $indexingEntity = $repository->create();
        $indexingEntity->setTargetId(1);
        $indexingEntity->setTargetParentId(100);
        $indexingEntity->setTargetEntityType('__PRODUCT');
        $indexingEntity->setTargetEntitySubtype('downloadable');
        $indexingEntity->setSiteId('site-id-1234');
        $indexingEntity->setLastAction(Actions::NO_ACTION);
        $indexingEntity->setLastActionTimestamp(null);
        $indexingEntity->setNextAction(Actions::UPSERT);
        $indexingEntity->setLockTimestamp(null);
        $indexingEntity->setIsIndexable(true);
        $savedIndexingEntity = $repository->save($indexingEntity);

        $lastActionTime = date('Y-m-d H:i:s');
        $savedIndexingEntity->setLastAction(Actions::UPSERT);
        $savedIndexingEntity->setLastActionTimestamp($lastActionTime);
        $savedIndexingEntity->setNextAction(Actions::UPSERT);
        $updatedIndexingEntity = $repository->save($savedIndexingEntity);

        $this->assertSame(
            100,
            $updatedIndexingEntity->getTargetParentId(),
        );
        $this->assertSame(
            Actions::UPSERT,
            $updatedIndexingEntity->getLastAction(),
        );
        $this->assertSame(
            $lastActionTime,
            $updatedIndexingEntity->getLastActionTimestamp(),
        );
        $this->assertSame(
            Actions::UPSERT,
            $updatedIndexingEntity->getNextAction(),
        );
        $this->assertSame(
            'downloadable',
            $updatedIndexingEntity->getTargetEntitySubtype(),
        );
    }

    public function testSave_Update_InvalidData(): void
    {
        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessageMatches('#Could not save Indexing Entity: .*#');

        $repository = $this->instantiateTestObject();
        $indexingEntity = $repository->create();
        $indexingEntity->setTargetId(1);
        $indexingEntity->setTargetParentId(2);
        $indexingEntity->setTargetEntityType('__PRODUCT');
        $indexingEntity->setSiteId('site-id-1234');
        $indexingEntity->setLastAction(Actions::NO_ACTION);
        $indexingEntity->setLastActionTimestamp(null);
        $indexingEntity->setNextAction(Actions::UPSERT);
        $indexingEntity->setLockTimestamp(null);
        $indexingEntity->setIsIndexable(true);
        $savedIndexingEntity = $repository->save($indexingEntity);

        $savedIndexingEntity->setData('target_id', 'not an integer'); // @phpstan-ignore-line
        $repository->save($savedIndexingEntity);
    }

    public function testSave_HandlesAlreadyExistsException(): void
    {
        $indexingEntity = $this->createIndexingEntity();

        $mockMessage = 'Entity Already Exists';
        $this->expectException(AlreadyExistsException::class);
        $this->expectExceptionMessage($mockMessage);

        $exception = new AlreadyExistsException(__($mockMessage));
        $mockResourceModel = $this->getMockBuilder(\AthosCommerce\Feed\Model\ResourceModel\IndexingEntity::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockResourceModel->expects($this->once())
            ->method('save')
            ->willThrowException($exception);

        $repository = $this->instantiateTestObject([
            'indexingEntityResourceModel' => $mockResourceModel,
        ]);
        $repository->save($indexingEntity);
    }

    public function testSave_HandlesException(): void
    {
        $indexingEntity = $this->createIndexingEntity();

        $mockMessage = 'Some core exception message.';
        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessage(sprintf('Could not save Indexing Entity: %s', $mockMessage));

        $exception = new \Exception($mockMessage);
        $mockResourceModel = $this->getMockBuilder(\AthosCommerce\Feed\Model\ResourceModel\IndexingEntity::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockResourceModel->expects($this->once())
            ->method('save')
            ->willThrowException($exception);

        $repository = $this->instantiateTestObject([
            'indexingEntityResourceModel' => $mockResourceModel,
        ]);
        $repository->save($indexingEntity);
    }

    public function testDelete_RemovesIndexingEntity(): void
    {
        $repository = $this->instantiateTestObject();
        $indexingEntity = $this->createIndexingEntity();
        $entityId = $indexingEntity->getId();
        $this->assertNotNull($entityId);
        $repository->delete($indexingEntity);

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage(sprintf('No such entity with entity_id = %s', $entityId));
        $repository->getById((int)$entityId);
    }

    public function testDelete_HandlesLocalizedException(): void
    {
        $indexingEntity = $this->createIndexingEntity();

        $mockMessage = 'A localized exception message';
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage($mockMessage);

        $exception = new LocalizedException(__($mockMessage));
        $mockResourceModel = $this->getMockBuilder(\AthosCommerce\Feed\Model\ResourceModel\IndexingEntity::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockResourceModel->expects($this->once())
            ->method('delete')
            ->willThrowException($exception);

        $repository = $this->instantiateTestObject([
            'indexingEntityResourceModel' => $mockResourceModel,
        ]);
        $repository->delete($indexingEntity);
    }

    public function testDelete_HandlesException(): void
    {
        $indexingEntity = $this->createIndexingEntity();

        $mockMessage = 'Some core exception message.';
        $this->expectException(CouldNotDeleteException::class);
        $this->expectExceptionMessage(sprintf('Could not delete Indexing Entity: %s', $mockMessage));

        $exception = new \Exception($mockMessage);
        $mockResourceModel = $this->getMockBuilder(IndexingEntityResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockResourceModel->expects($this->once())
            ->method('delete')
            ->willThrowException($exception);

        $mockLogger = $this->getMockBuilder(AthosCommerceLogger::class)
            ->getMock();
        $mockLogger->expects($this->once())
            ->method('error')
            ->with(
                sprintf('Could not delete Indexing Entity: %s', $mockMessage),
                [
                    'exception' => \Exception::class,
                    'method' => 'AthosCommerce\Feed\Model\IndexingEntityRepository::delete',
                    'indexingEntity' => [
                        'entityId' => $indexingEntity->getId(),
                        'targetId' => $indexingEntity->getTargetId(),
                        'targetParentId' => $indexingEntity->getTargetParentId(),
                        'targetEntityType' => $indexingEntity->getTargetEntityType(),
                        'targetEntitySubType' => $indexingEntity->getTargetEntitySubtype(),
                        'siteId' => $indexingEntity->getSiteId(),
                    ],
                ],
            );

        $repository = $this->instantiateTestObject([
            'indexingEntityResourceModel' => $mockResourceModel,
            'logger' => $mockLogger,
        ]);
        $repository->delete($indexingEntity);
    }

    public function testDeleteById_NotExists(): void
    {
        $entityId = -1;
        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage(sprintf('No such entity with entity_id = %s', $entityId));

        $repository = $this->instantiateTestObject();
        $repository->deleteById($entityId);
    }

    public function testDeleteById_Exists(): void
    {
        $repository = $this->instantiateTestObject();
        $indexingEntity = $this->createIndexingEntity();
        $entityId = $indexingEntity->getId();
        try {
            $repository->getById((int)$entityId);
        } catch (\Exception $exception) {
            $this->fail('Failed to create Indexing Entity for test: ' . $exception->getMessage());
        }

        $repository->deleteById((int)$entityId);

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage(sprintf('No such entity with entity_id = %s', $entityId));
        $repository->getById((int)$entityId);
    }

    public function testGetList_NoResults(): void
    {
        $searchCriteriaBuilderFactory = $this->objectManager->get(SearchCriteriaBuilderFactory::class);
        $searchCriteriaBuilder = $searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter(
            'entity_id',
            0,
            'lt',
        );
        $searchCriteria = $searchCriteriaBuilder->create();

        $repository = $this->instantiateTestObject();
        $searchResult = $repository->getList($searchCriteria);

        $this->assertEquals(0, $searchResult->getTotalCount());
        $this->assertEmpty($searchResult->getItems());
        $this->assertSame($searchCriteria, $searchResult->getSearchCriteria());
    }

    public function testGetList_Results(): void
    {
        $siteId = 'site-id';
        $this->cleanIndexingEntities($siteId);

        $this->createIndexingEntity([
            \AthosCommerce\Feed\Model\IndexingEntity::TARGET_ENTITY_TYPE => '__CATEGORY',
            'target_id' => 2,
            IndexingEntity::SITE_ID => $siteId,
        ]);
        $this->createIndexingEntity([
            IndexingEntity::TARGET_ENTITY_TYPE => '__CMS',
            IndexingEntity::TARGET_ID => 1,
            IndexingEntity::SITE_ID => $siteId,
        ]);
        $this->createIndexingEntity([
            IndexingEntity::TARGET_ENTITY_TYPE => '__PRODUCT',
            IndexingEntity::TARGET_ENTITY_SUBTYPE => 'simple',
            IndexingEntity::TARGET_ID => 1,
            IndexingEntity::TARGET_PARENT_ID => 5,
            IndexingEntity::SITE_ID => $siteId,
        ]);
        $this->createIndexingEntity([
            IndexingEntity::TARGET_ENTITY_TYPE => '__PRODUCT',
            IndexingEntity::TARGET_ENTITY_SUBTYPE => 'simple',
            IndexingEntity::TARGET_ID => 2,
            IndexingEntity::SITE_ID => $siteId,
        ]);
        $this->createIndexingEntity([
            IndexingEntity::TARGET_ENTITY_TYPE => '__PRODUCT',
            IndexingEntity::TARGET_ENTITY_SUBTYPE => 'simple',
            IndexingEntity::TARGET_ID => 3,
            IndexingEntity::SITE_ID => $siteId,
        ]);
        $this->createIndexingEntity([
            IndexingEntity::TARGET_ENTITY_TYPE => '__PRODUCT',
            IndexingEntity::TARGET_ENTITY_SUBTYPE => 'virtual',
            IndexingEntity::TARGET_ID => 4,
            IndexingEntity::SITE_ID => $siteId,
        ]);
        $this->createIndexingEntity([
            IndexingEntity::TARGET_ENTITY_TYPE => '__PRODUCT',
            IndexingEntity::TARGET_ENTITY_SUBTYPE => 'simple',
            IndexingEntity::TARGET_ID => 5,
            IndexingEntity::SITE_ID => $siteId,
        ]);

        $searchCriteriaBuilderFactory = $this->objectManager->get(SearchCriteriaBuilderFactory::class);
        $searchCriteriaBuilder = $searchCriteriaBuilderFactory->create();

        $sortOrderBuilder = $this->objectManager->get(SortOrderBuilder::class);
        $sortOrderBuilder->setField('target_id');
        $sortOrderBuilder->setAscendingDirection();
        $sortOrder = $sortOrderBuilder->create();
        $searchCriteriaBuilder->setSortOrders([$sortOrder]);

        $searchCriteriaBuilder->addFilter(
            IndexingEntity::TARGET_ENTITY_TYPE,
            '__PRODUCT',
        );
        $searchCriteriaBuilder->addFilter(
            IndexingEntity::SITE_ID,
            $siteId,
        );
        $searchCriteriaBuilder->addFilter(
            IndexingEntity::TARGET_ENTITY_SUBTYPE,
            'simple',
        );
        $searchCriteriaBuilder->setPageSize(2);
        $searchCriteriaBuilder->setCurrentPage(2);
        $searchCriteria = $searchCriteriaBuilder->create();

        $repository = $this->instantiateTestObject();
        $searchResult = $repository->getList($searchCriteria, true);

        $this->assertSame($searchCriteria, $searchResult->getSearchCriteria());
        // total number of items available
        $this->assertEquals(4, $searchResult->getTotalCount());
        $items = $searchResult->getItems();
        // paginated number of items on this page
        $this->assertCount(2, $items);
        // get target ids and ensure we are on page 2
        $targetIds = array_map(static fn(\AthosCommerce\Feed\Api\Data\IndexingEntityInterface $indexingEntity): int => (
        $indexingEntity->getTargetId()
        ), $items);
        $this->assertContains(3, $targetIds);
        $this->assertContains(5, $targetIds);

        $searchResult = $repository->getList($searchCriteria, false);
        $this->assertSame($searchCriteria, $searchResult->getSearchCriteria());
        // number of items in results
        $this->assertEquals(2, $searchResult->getTotalCount());

        $this->cleanIndexingEntities($siteId);
    }

    public function testGetUniqueEntityTypes_ReturnsEmptyArray_WhenTableIsEmpty(): void
    {
        $siteId = 'site-id';
        $this->cleanIndexingEntities($siteId);

        $repository = $this->instantiateTestObject();
        $result = $repository->getUniqueEntityTypes($siteId);

        $this->assertCount(0, $result);
    }

    public function testGetUniqueEntityTypes_ReturnsArrayOfTypesForSiteId(): void
    {
        $siteId = 'site-id';
        $this->cleanIndexingEntities($siteId);
        $this->cleanIndexingEntities($siteId . '2');

        $this->createIndexingEntity([
            IndexingEntity::SITE_ID => $siteId,
            IndexingEntity::TARGET_ENTITY_TYPE => '__PRODUCT',
            IndexingEntity::TARGET_ENTITY_SUBTYPE => 'simple',
            IndexingEntity::TARGET_ID => 1,
            IndexingEntity::NEXT_ACTION => \AthosCommerce\Feed\Model\Source\Actions::UPSERT,
            IndexingEntity::IS_INDEXABLE => true,
        ]);
        $this->createIndexingEntity([
            IndexingEntity::SITE_ID => $siteId,
            IndexingEntity::TARGET_ENTITY_TYPE => '__PRODUCT',
            IndexingEntity::TARGET_ENTITY_SUBTYPE => 'simple',
            IndexingEntity::TARGET_ID => 2,
            IndexingEntity::NEXT_ACTION => Actions::UPSERT,
            IndexingEntity::IS_INDEXABLE => true,
        ]);
        $this->createIndexingEntity([
            IndexingEntity::SITE_ID => $siteId,
            IndexingEntity::TARGET_ENTITY_TYPE => 'CUSTOM_TYPE',
            IndexingEntity::TARGET_ENTITY_SUBTYPE => 'simple',
            IndexingEntity::TARGET_ID => 4,
            IndexingEntity::NEXT_ACTION => Actions::NO_ACTION,
            IndexingEntity::IS_INDEXABLE => false,
        ]);
        $this->createIndexingEntity([
            IndexingEntity::SITE_ID => $siteId,
            IndexingEntity::TARGET_ENTITY_TYPE => '__CATEGORY',
            IndexingEntity::TARGET_ENTITY_SUBTYPE => 'category',
            IndexingEntity::TARGET_ID => 1,
            IndexingEntity::NEXT_ACTION => Actions::UPSERT,
            IndexingEntity::IS_INDEXABLE => true,
        ]);
        $this->createIndexingEntity([
            IndexingEntity::SITE_ID => $siteId,
            IndexingEntity::TARGET_ENTITY_TYPE => '__CATEGORY',
            IndexingEntity::TARGET_ENTITY_SUBTYPE => 'category',
            IndexingEntity::TARGET_ID => 2,
            IndexingEntity::NEXT_ACTION => Actions::UPSERT,
            IndexingEntity::IS_INDEXABLE => true,
        ]);
        $this->createIndexingEntity([
            IndexingEntity::SITE_ID => $siteId,
            IndexingEntity::TARGET_ENTITY_TYPE => '__CMS',
            IndexingEntity::TARGET_ENTITY_SUBTYPE => 'category',
            IndexingEntity::TARGET_ID => 3,
            IndexingEntity::NEXT_ACTION => Actions::DELETE,
            IndexingEntity::IS_INDEXABLE => true,
        ]);
        $this->createIndexingEntity([
            IndexingEntity::SITE_ID => $siteId,
            IndexingEntity::TARGET_ENTITY_TYPE => '__CATEGORY',
            IndexingEntity::TARGET_ENTITY_SUBTYPE => 'category',
            IndexingEntity::TARGET_ID => 4,
            IndexingEntity::NEXT_ACTION => Actions::NO_ACTION,
            IndexingEntity::IS_INDEXABLE => false,
        ]);
        $this->createIndexingEntity([
            IndexingEntity::SITE_ID => $siteId . '2',
            IndexingEntity::TARGET_ENTITY_TYPE => 'OTHER_CUSTOM_TYPE',
            IndexingEntity::TARGET_ENTITY_SUBTYPE => 'category',
            IndexingEntity::TARGET_ID => 1,
            IndexingEntity::NEXT_ACTION => Actions::UPSERT,
            IndexingEntity::IS_INDEXABLE => true,
        ]);

        $repository = $this->instantiateTestObject();

        $result = $repository->getUniqueEntityTypes($siteId);
        $this->assertContains('__CATEGORY', $result);
        $this->assertContains('__CMS', $result);
        $this->assertContains('__PRODUCT', $result);
        $this->assertContains('CUSTOM_TYPE', $result);
        $this->assertNotContains('OTHER_CUSTOM_TYPE', $result);

        $result = $repository->getUniqueEntityTypes($siteId . '2');
        $this->assertNotContains('__CATEGORY', $result);
        $this->assertNotContains('__CMS', $result);
        $this->assertNotContains('__PRODUCT', $result);
        $this->assertNotContains('CUSTOM_TYPE', $result);
        $this->assertContains('OTHER_CUSTOM_TYPE', $result);
    }

    /**
     * @param mixed[] $data
     * @param bool $save
     *
     * @return \AthosCommerce\Feed\Api\Data\IndexingEntityInterface
     * @throws AlreadyExistsException
     */
    private function createIndexingEntity(array $data = [], bool $save = true): IndexingEntityInterface
    {
        // use objectManager::create to stop caching between tests
        /** @var IndexingEntityInterface $indexingEntity */
        $indexingEntity = $this->objectManager->create(IndexingEntityInterface::class);
        $indexingEntity->setTargetEntityType($data['target_entity_type'] ?? '__PRODUCT');
        $indexingEntity->setTargetEntitySubtype($data['target_entity_subtype'] ?? null);
        $indexingEntity->setTargetId($data['target_id'] ?? 1);
        $indexingEntity->setTargetParentId($data['target_parent_id'] ?? null);
        $indexingEntity->setSiteId(
            $data['site_id'] ?? 'site-id-' . random_int(0, 999999999),
        );
        $indexingEntity->setNextAction($data['next_action'] ??
            \AthosCommerce\Feed\Model\Source\Actions::UPSERT);
        $indexingEntity->setLockTimestamp($data['lock_timestamp'] ?? null);
        $indexingEntity->setLastAction($data['last_action'] ?? Actions::UPSERT);
        $indexingEntity->setLastActionTimestamp($data['last_action_timestamp'] ?? null);
        $indexingEntity->setIsIndexable($data['is_indexable'] ?? true);

        if ($save) {
            $resourceModel = $this->objectManager->get(\AthosCommerce\Feed\Model\ResourceModel\IndexingEntity::class);
            $resourceModel->save($indexingEntity);
        }

        return $indexingEntity;
    }

    /**
     * @return MockObject|IndexingEntityResourceModel
     */
    private function getMockIndexingEntityResource(): MockObject
    {
        return $this->getMockBuilder(IndexingEntityResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
