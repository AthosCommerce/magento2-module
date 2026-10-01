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

namespace AthosCommerce\Feed\Test\Unit\Model\LiveIndexing;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Config;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use AthosCommerce\Feed\Model\LiveIndexing\DeleteProcessor;
use AthosCommerce\Feed\Model\LiveIndexing\Processor;
use AthosCommerce\Feed\Model\LiveIndexing\UpdateProcessor;
use AthosCommerce\Feed\Service\Provider\IndexingEntityProvider;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AthosCommerce\Feed\Model\LiveIndexing\Processor
 */
class ProcessorTest extends TestCase
{
    /**
     * A failing store still resets the feed context, so the next store in the same run does not
     * inherit its store emulation or customer context.
     */
    public function testContextIsResetWhenProcessingFails(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getPayloadByStoreId')->willReturn('{"includeOutOfStock":true}');
        $config->method('getRequestPerMinuteByStoreId')
            ->willThrowException(new \RuntimeException('config unavailable'));
        $specificationBuilder = $this->createMock(SpecificationBuilderInterface::class);
        $specificationBuilder->method('build')
            ->willReturn($this->createMock(FeedSpecificationInterface::class));
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('unserialize')->willReturn(['includeOutOfStock' => true]);
        $contextManager = $this->createMock(ContextManagerInterface::class);
        $contextManager->expects($this->once())->method('setContextFromSpecification');
        $contextManager->expects($this->once())->method('resetContext');
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $store->method('getCode')->willReturn('default');

        $processor = new Processor(
            $this->createMock(IndexingEntityProvider::class),
            $config,
            $specificationBuilder,
            $serializer,
            $contextManager,
            $this->createMock(DeleteProcessor::class),
            $this->createMock(UpdateProcessor::class),
            $this->createMock(AthosCommerceLogger::class),
            $this->createMock(StoreManagerInterface::class)
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('config unavailable');
        $processor->execute($store, 'site-1');
    }
}
