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

namespace AthosCommerce\Feed\Test\Unit\Model\Metric;

require_once dirname(__DIR__, 2) . '/_files/bootstrap-stubs.php';

use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Event\ManagerInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Api\AppConfigInterface;
use AthosCommerce\Feed\Model\Metric\Collector;
use AthosCommerce\Feed\Model\Metric\MetricProviderInterface;
use AthosCommerce\Feed\Model\Metric\OutputInterface;
use AthosCommerce\Feed\Model\Metric\View\FormatterInterface;

class CollectorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var OutputInterface
     */
    private $outputMock;

    /**
     * @var FormatterInterface
     */
    private $formatterMock;

    /**
     * @var AppConfigInterface
     */
    private $appConfigMock;

    /**
     * @var ManagerInterface
     */
    private $eventManagerMock;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactoryMock;

    /**
     * @var AthosCommerceLogger
     */
    private $loggerMock;
    private $metricProviderMock;
    private $collector;

    public function setUp(): void
    {
        $this->metricProviderMock = $this->createMock(MetricProviderInterface::class);
        $this->outputMock = $this->createMock(OutputInterface::class);
        $this->formatterMock = $this->createMock(FormatterInterface::class);
        $this->appConfigMock = $this->createMock(AppConfigInterface::class);
        $this->eventManagerMock = $this->createMock(ManagerInterface::class);
        $this->dataObjectFactoryMock = $this->createMock(DataObjectFactory::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);
        $this->collector = new Collector(
            $this->outputMock,
            $this->formatterMock,
            $this->appConfigMock,
            $this->eventManagerMock,
            $this->dataObjectFactoryMock,
            $this->loggerMock,
            false,
            ['test' => [$this->metricProviderMock]]
        );
    }

    public function testCollect()
    {
        $dataObject = new DataObject(['result' => true]);
        $data = [
            'data' => [
                'collector' => $this->collector,
                'result' => true,
                'code' => 'test', 'force' => false
            ]
        ];
        $this->appConfigMock->expects($this->once())
            ->method('isDebug')
            ->willReturn(true);
        $this->dataObjectFactoryMock->expects($this->once())
            ->method('create')
            ->with($data)
            ->willReturn($dataObject);
        $this->eventManagerMock->expects($this->once())
            ->method('dispatch')
            ->with('athoscommerce_feed_is_metric_allowed', ['container' => $dataObject]);
        $this->metricProviderMock->expects($this->once())
            ->method('getMetrics')
            ->with(['additional_data'], [])
            ->willReturn(['size', 'memory']);

        $this->collector->collect('test', null, ['additional_data']);
    }


    public function testCollectExceptionCase()
    {
        $dataObject = new DataObject(['result' => true]);
        $data = [
            'data' => [
                'collector' => $this->collector,
                'result' => true,
                'code' => 'test', 'force' => false
            ]
        ];
        $this->appConfigMock->expects($this->once())
            ->method('isDebug')
            ->willReturn(true);
        $this->dataObjectFactoryMock->expects($this->once())
            ->method('create')
            ->with($data)
            ->willReturn($dataObject);
        $this->eventManagerMock->expects($this->once())
            ->method('dispatch')
            ->with('athoscommerce_feed_is_metric_allowed', ['container' => $dataObject]);
        $this->metricProviderMock->expects($this->once())
            ->method('getMetrics')
            ->with(['additional_data'], [])
            ->willThrowException(new \Exception());
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->withAnyParameters();

        $this->collector->collect('test', null, ['additional_data']);
    }

    public function testPrint()
    {
        $dataObject = new DataObject(['result' => true]);
        $data = [
            'data' => [
                'collector' => $this->collector,
                'result' => true,
                'code' => 'test', 'force' => false
            ]
        ];
        $this->appConfigMock->expects($this->once())
            ->method('isDebug')
            ->willReturn(true);
        $this->dataObjectFactoryMock->expects($this->once())
            ->method('create')
            ->with($data)
            ->willReturn($dataObject);
        $this->eventManagerMock->expects($this->once())
            ->method('dispatch')
            ->with('athoscommerce_feed_is_metric_allowed', ['container' => $dataObject]);

        $this->formatterMock->expects($this->once())
            ->method('format')
            ->with(
                ['__print_type__' => Collector::PRINT_TYPE_FROM_PREVIOUS],
                'test'
            )
            ->willReturn('formatted_metrics');
        $this->outputMock->expects($this->once())
            ->method('print')
            ->with('formatted_metrics');

        $this->collector->print('test');
    }


    public function testPrintExceptionCase()
    {
        $dataObject = new DataObject(['result' => true]);
        $data = [
            'data' => [
                'collector' => $this->collector,
                'result' => true,
                'code' => 'test', 'force' => false
            ]
        ];
        $this->appConfigMock->expects($this->once())
            ->method('isDebug')
            ->willReturn(true);
        $this->dataObjectFactoryMock->expects($this->once())
            ->method('create')
            ->with($data)
            ->willReturn($dataObject);
        $this->eventManagerMock->expects($this->once())
            ->method('dispatch')
            ->with('athoscommerce_feed_is_metric_allowed', ['container' => $dataObject]);

        $this->formatterMock->expects($this->once())
            ->method('format')
            ->with(
                ['__print_type__' => Collector::PRINT_TYPE_FROM_PREVIOUS],
                'test'
            )
            ->willReturn('formatted_metrics');
        $this->outputMock->expects($this->once())
            ->method('print')
            ->willThrowException(new \Exception());
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->withAnyParameters();

        $this->collector->print('test');
    }
}
