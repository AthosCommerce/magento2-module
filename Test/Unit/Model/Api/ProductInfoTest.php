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

namespace AthosCommerce\Feed\Test\Unit\Model\Api;

require_once dirname(__DIR__, 2) . '/_files/bootstrap-stubs.php';

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Api\Data\ProductInfoResponseInterface;
use AthosCommerce\Feed\Api\Data\ProductInfoResponseInterfaceFactory;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Api\ProductInfo;
use AthosCommerce\Feed\Model\CollectionProcessor;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use AthosCommerce\Feed\Model\ItemsGenerator;
use AthosCommerce\Feed\Model\Task\TaskPayloadProvider;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\TestCase;

class ProductInfoTest extends TestCase
{
    private $collectionProcessorMock;
    private $itemsGeneratorMock;
    private $specificationBuilderMock;
    private $scopeConfigMock;
    private $serializerMock;
    private $responseFactoryMock;
    private $responseMock;
    private $loggerMock;
    private $taskPayloadProviderMock;
    private $contextManagerMock;
    private $model;

    protected function setUp(): void
    {
        $this->collectionProcessorMock = $this->createMock(CollectionProcessor::class);
        $this->itemsGeneratorMock = $this->createMock(ItemsGenerator::class);
        $this->specificationBuilderMock = $this->createMock(SpecificationBuilderInterface::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->responseFactoryMock = $this->createMock(ProductInfoResponseInterfaceFactory::class);
        $this->responseMock = $this->createMock(ProductInfoResponseInterface::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);
        $this->taskPayloadProviderMock = $this->createMock(TaskPayloadProvider::class);
        $this->contextManagerMock = $this->createMock(ContextManagerInterface::class);

        $this->responseFactoryMock->method('create')->willReturn($this->responseMock);
        $this->responseMock->method('setProductIds')->willReturnSelf();
        $this->responseMock->method('setProductInfo')->willReturnSelf();
        $this->responseMock->method('setMessage')->willReturnSelf();
        $this->loggerMock->method('info');
        $this->loggerMock->method('error');

        $this->model = new ProductInfo(
            $this->collectionProcessorMock,
            $this->itemsGeneratorMock,
            $this->specificationBuilderMock,
            $this->scopeConfigMock,
            $this->serializerMock,
            $this->responseFactoryMock,
            $this->loggerMock,
            $this->taskPayloadProviderMock,
            $this->contextManagerMock
        );
    }

    public function testGetInfoResetsContextOnSuccess(): void
    {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $collectionMock = $this->createMock(Collection::class);
        $itemsData = [['entity_id' => 10]];
        $select = new class {
            public function __toString(): string
            {
                return 'SELECT success';
            }
        };

        $this->scopeConfigMock->expects($this->once())->method('getValue')->willReturn(['payload' => true]);
        $this->specificationBuilderMock->expects($this->once())->method('build')->with(['payload' => true])->willReturn($feedSpecificationMock);
        $this->contextManagerMock->expects($this->once())->method('setContextFromSpecification')->with($feedSpecificationMock);
        $this->contextManagerMock->expects($this->once())->method('resetContext');
        $this->itemsGeneratorMock->expects($this->once())->method('resetDataProviders')->with($feedSpecificationMock);
        $this->collectionProcessorMock->expects($this->once())->method('getCollection')->with($feedSpecificationMock)->willReturn($collectionMock);
        $collectionMock->expects($this->once())->method('addFieldToFilter')->with('entity_id', ['in' => [10]])->willReturnSelf();
        $collectionMock->expects($this->once())->method('load')->willReturnSelf();
        $this->collectionProcessorMock->expects($this->once())->method('processAfterLoad')->with($collectionMock, $feedSpecificationMock);
        $collectionMock->expects($this->once())->method('getSize')->willReturn(1);
        $collectionMock->expects($this->once())->method('getItems')->willReturn([]);
        $this->itemsGeneratorMock->expects($this->once())->method('generate')->with([], $feedSpecificationMock)->willReturn($itemsData);
        $this->itemsGeneratorMock->expects($this->once())->method('resetDataProvidersAfterFetchItems')->with($feedSpecificationMock);
        $this->collectionProcessorMock->expects($this->once())->method('processAfterFetchItems')->with($collectionMock, $feedSpecificationMock);
        $collectionMock->expects($this->once())->method('getSelect')->willReturn($select);
        $this->responseMock->expects($this->once())->method('setProductIds')->with([10])->willReturnSelf();
        $this->responseMock->expects($this->once())->method('setProductInfo')->with($itemsData)->willReturnSelf();

        $result = $this->model->getInfo(10, 1);

        $this->assertSame($this->responseMock, $result);
    }

    public function testGetInfoResetsContextOnEarlyReturnAfterContextSetup(): void
    {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $collectionMock = $this->createMock(Collection::class);
        $select = new class {
            public function __toString(): string
            {
                return 'SELECT empty';
            }
        };

        $this->scopeConfigMock->expects($this->once())->method('getValue')->willReturn(['payload' => true]);
        $this->specificationBuilderMock->expects($this->once())->method('build')->willReturn($feedSpecificationMock);
        $this->contextManagerMock->expects($this->once())->method('setContextFromSpecification')->with($feedSpecificationMock);
        $this->contextManagerMock->expects($this->once())->method('resetContext');
        $this->itemsGeneratorMock->expects($this->once())->method('resetDataProviders')->with($feedSpecificationMock);
        $this->collectionProcessorMock->expects($this->once())->method('getCollection')->willReturn($collectionMock);
        $collectionMock->expects($this->once())->method('addFieldToFilter')->with('entity_id', ['in' => [11]])->willReturnSelf();
        $collectionMock->expects($this->once())->method('load')->willReturnSelf();
        $this->collectionProcessorMock->expects($this->once())->method('processAfterLoad')->with($collectionMock, $feedSpecificationMock);
        $collectionMock->expects($this->once())->method('getSize')->willReturn(0);
        $collectionMock->expects($this->once())->method('getSelect')->willReturn($select);
        $this->responseMock->expects($this->once())->method('setProductIds')->with([11])->willReturnSelf();
        $this->responseMock->expects($this->once())->method('setProductInfo')->with([])->willReturnSelf();
        $this->responseMock->expects($this->once())->method('setMessage')->with($this->stringContains('No products found'))->willReturnSelf();

        $result = $this->model->getInfo(11, 2);

        $this->assertSame($this->responseMock, $result);
    }

    public function testGetInfoResetsContextOnException(): void
    {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $collectionMock = $this->createMock(Collection::class);

        $this->scopeConfigMock->expects($this->once())->method('getValue')->willReturn(['payload' => true]);
        $this->specificationBuilderMock->expects($this->once())->method('build')->willReturn($feedSpecificationMock);
        $this->contextManagerMock->expects($this->once())->method('setContextFromSpecification')->with($feedSpecificationMock);
        $this->contextManagerMock->expects($this->once())->method('resetContext');
        $this->itemsGeneratorMock->expects($this->once())->method('resetDataProviders')->with($feedSpecificationMock);
        $this->collectionProcessorMock->expects($this->once())->method('getCollection')->willReturn($collectionMock);
        $collectionMock->expects($this->once())->method('addFieldToFilter')->with('entity_id', ['in' => [12]])->willReturnSelf();
        $collectionMock->expects($this->once())->method('load')->willReturnSelf();
        $this->collectionProcessorMock->expects($this->once())
            ->method('processAfterLoad')
            ->with($collectionMock, $feedSpecificationMock)
            ->willThrowException(new \RuntimeException('boom'));
        $this->responseMock->expects($this->once())->method('setProductIds')->with([12])->willReturnSelf();
        $this->responseMock->expects($this->once())->method('setProductInfo')->with([])->willReturnSelf();
        $this->responseMock->expects($this->once())->method('setMessage')->with('boom')->willReturnSelf();

        $result = $this->model->getInfo(12, 3);

        $this->assertSame($this->responseMock, $result);
    }
}
