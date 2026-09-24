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

namespace AthosCommerce\Feed\Model\Api {
    if (!class_exists(MagentoEntityInterfaceFactory::class)) {
        class MagentoEntityInterfaceFactory
        {
            public function create(array $data = [])
            {
                return (object)$data;
            }
        }
    }
}

namespace AthosCommerce\Feed\Test\Unit\Model {

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\CollectionProcessor;
use AthosCommerce\Feed\Model\Config;
use AthosCommerce\Feed\Model\EntityDiscovery;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\RelationsProvider;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use AthosCommerce\Feed\Model\Api\MagentoEntityInterfaceFactory;
use AthosCommerce\Feed\Service\Action\AddIndexingEntitiesActionInterface;
use AthosCommerce\Feed\Service\Action\SetIndexingEntitiesToDeleteActionInterface;
use AthosCommerce\Feed\Service\Action\SetIndexingEntitiesToUpdateActionInterface;
use AthosCommerce\Feed\Service\Provider\Api\IndexingEntityProviderInterface;
use AthosCommerce\Feed\Service\Provider\MagentoEntityProvider;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityDiscoveryTest extends TestCase
{
    /** @var StoreManagerInterface|MockObject */
    private $storeManagerMock;

    /** @var Config|MockObject */
    private $configMock;

    /** @var AthosCommerceLogger|MockObject */
    private $loggerMock;

    /** @var AddIndexingEntitiesActionInterface|MockObject */
    private $addIndexingEntitiesActionMock;

    /** @var MagentoEntityInterfaceFactory|MockObject */
    private $magentoEntityInterfaceFactoryMock;

    /** @var CollectionProcessor|MockObject */
    private $collectionProcessorMock;

    /** @var SpecificationBuilderInterface|MockObject */
    private $specificationBuilderMock;

    /** @var SerializerInterface|MockObject */
    private $serializerMock;

    /** @var ContextManagerInterface|MockObject */
    private $contextManagerMock;

    /** @var RelationsProvider|MockObject */
    private $productRelationsProviderMock;

    /** @var MagentoEntityProvider|MockObject */
    private $magentoEntityProviderMock;

    /** @var IndexingEntityProviderInterface|MockObject */
    private $indexingEntityProviderMock;

    /** @var ResourceConnection|MockObject */
    private $resourceMock;

    /** @var AdapterInterface|MockObject */
    private $connectionMock;

    /** @var Select|MockObject */
    private $selectMock;

    /** @var SetIndexingEntitiesToDeleteActionInterface|MockObject */
    private $setIndexingEntitiesToDeleteActionMock;

    /** @var SetIndexingEntitiesToUpdateActionInterface|MockObject */
    private $setIndexingEntitiesToUpdateActionMock;

    /** @var EntityDiscovery */
    private $entityDiscovery;

    protected function setUp(): void
    {
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->configMock = $this->createMock(Config::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);
        $this->addIndexingEntitiesActionMock = $this->createMock(AddIndexingEntitiesActionInterface::class);
        $this->magentoEntityInterfaceFactoryMock = $this->createMock(MagentoEntityInterfaceFactory::class);
        $this->collectionProcessorMock = $this->createMock(CollectionProcessor::class);
        $this->specificationBuilderMock = $this->createMock(SpecificationBuilderInterface::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->contextManagerMock = $this->createMock(ContextManagerInterface::class);
        $this->productRelationsProviderMock = $this->createMock(RelationsProvider::class);
        $this->magentoEntityProviderMock = $this->createMock(MagentoEntityProvider::class);
        $this->indexingEntityProviderMock = $this->createMock(IndexingEntityProviderInterface::class);
        $this->resourceMock = $this->createMock(ResourceConnection::class);
        $this->connectionMock = $this->createMock(AdapterInterface::class);
        $this->selectMock = $this->createMock(Select::class);
        $this->setIndexingEntitiesToDeleteActionMock = $this->createMock(SetIndexingEntitiesToDeleteActionInterface::class);
        $this->setIndexingEntitiesToUpdateActionMock = $this->createMock(SetIndexingEntitiesToUpdateActionInterface::class);

        $this->resourceMock->method('getConnection')->willReturn($this->connectionMock);
        $this->resourceMock->method('getTableName')->willReturnCallback(static function (string $tableName): string {
            return $tableName;
        });

        $this->selectMock->method('from')->willReturnSelf();
        $this->selectMock->method('where')->willReturnSelf();
        $this->selectMock->method('order')->willReturnSelf();
        $this->selectMock->method('limit')->willReturnSelf();
        $this->connectionMock->method('select')->willReturn($this->selectMock);

        $this->entityDiscovery = new EntityDiscovery(
            $this->storeManagerMock,
            $this->configMock,
            $this->loggerMock,
            $this->addIndexingEntitiesActionMock,
            $this->magentoEntityInterfaceFactoryMock,
            $this->collectionProcessorMock,
            $this->specificationBuilderMock,
            $this->serializerMock,
            $this->contextManagerMock,
            $this->productRelationsProviderMock,
            $this->magentoEntityProviderMock,
            $this->indexingEntityProviderMock,
            $this->resourceMock,
            $this->setIndexingEntitiesToDeleteActionMock,
            $this->setIndexingEntitiesToUpdateActionMock
        );
    }

    public function testExecuteSetsAndResetsContextForDiscoveryCollection(): void
    {
        $storeMock = $this->createConfiguredMock(StoreInterface::class, [
            'getId' => 2,
            'getCode' => 'store-a',
        ]);
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);

        $this->storeManagerMock->expects($this->once())
            ->method('getStores')
            ->with(false)
            ->willReturn([$storeMock]);

        $this->configMock->method('getEndpointByStoreId')->with(2)->willReturn('https://example.test');
        $this->configMock->method('isLiveIndexingEnabled')->with(2)->willReturn(true);
        $this->configMock->method('getSiteIdByStoreId')->with(2)->willReturn('site-1');
        $this->configMock->method('getPayloadByStoreId')->with(2)->willReturn('payload-store-a');
        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with('payload-store-a')
            ->willReturn(['includeOutOfStock' => false]);

        $this->specificationBuilderMock->expects($this->once())
            ->method('build')
            ->with(['includeOutOfStock' => false, 'store' => 'store-a'])
            ->willReturn($feedSpecificationMock);
        $feedSpecificationMock->expects($this->once())
            ->method('setStoreCode')
            ->with('store-a')
            ->willReturnSelf();

        $this->contextManagerMock->expects($this->once())
            ->method('setContextFromSpecification')
            ->with($feedSpecificationMock);
        $this->contextManagerMock->expects($this->once())
            ->method('resetContext');

        $this->connectionMock->expects($this->once())
            ->method('fetchCol')
            ->with($this->selectMock)
            ->willReturn([]);

        $this->magentoEntityProviderMock->expects($this->once())
            ->method('getMagentoEntityIds')
            ->with($feedSpecificationMock)
            ->willReturn($this->createIdGenerator([[]]));

        $this->entityDiscovery->execute();
    }

    public function testExecuteResetsContextWhenDiscoveryCollectionFails(): void
    {
        $storeMock = $this->createConfiguredMock(StoreInterface::class, [
            'getId' => 6,
            'getCode' => 'store-b',
        ]);
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);

        $this->storeManagerMock->expects($this->once())
            ->method('getStores')
            ->with(false)
            ->willReturn([$storeMock]);

        $this->configMock->method('getEndpointByStoreId')->with(6)->willReturn('https://example.test');
        $this->configMock->method('isLiveIndexingEnabled')->with(6)->willReturn(true);
        $this->configMock->method('getSiteIdByStoreId')->with(6)->willReturn('site-2');
        $this->configMock->method('getPayloadByStoreId')->with(6)->willReturn('payload-store-b');
        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with('payload-store-b')
            ->willReturn(['includeOutOfStock' => false]);

        $this->specificationBuilderMock->expects($this->once())
            ->method('build')
            ->with(['includeOutOfStock' => false, 'store' => 'store-b'])
            ->willReturn($feedSpecificationMock);
        $feedSpecificationMock->expects($this->once())
            ->method('setStoreCode')
            ->with('store-b')
            ->willReturnSelf();

        $this->contextManagerMock->expects($this->once())
            ->method('setContextFromSpecification')
            ->with($feedSpecificationMock);
        $this->contextManagerMock->expects($this->once())
            ->method('resetContext');

        $this->connectionMock->expects($this->once())
            ->method('fetchCol')
            ->with($this->selectMock)
            ->willReturn([]);

        $this->magentoEntityProviderMock->expects($this->once())
            ->method('getMagentoEntityIds')
            ->with($feedSpecificationMock)
            ->willReturn($this->createThrowingGenerator(new \RuntimeException('stock filter failure')));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('[Discovery] error for store-b/site-2: stock filter failure'));

        $this->entityDiscovery->execute();
    }

    /**
     * @param array $batches
     * @return \Generator
     */
    private function createIdGenerator(array $batches): \Generator
    {
        foreach ($batches as $batch) {
            yield $batch;
        }
    }

    /**
     * @param \Throwable $exception
     * @return \Generator
     */
    private function createThrowingGenerator(\Throwable $exception): \Generator
    {
        throw $exception;
        yield [];
    }
}
}
