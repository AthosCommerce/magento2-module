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

use AthosCommerce\Feed\Api\Data\CronStatusInterface;
use AthosCommerce\Feed\Api\Data\CronStatusInterfaceFactory;
use AthosCommerce\Feed\Api\Data\CronStatusListInterface;
use AthosCommerce\Feed\Api\Data\CronStatusListInterfaceFactory;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Api\GetCronStatus;
use Magento\Cron\Model\ResourceModel\Schedule\Collection;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Cron\Model\Schedule;
use PHPUnit\Framework\TestCase;

class GetCronStatusTest extends TestCase
{
    /**
     * @var CollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $collectionFactoryMock;

    /**
     * @var CronStatusListInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $responseFactoryMock;

    /**
     * @var CronStatusInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $itemFactoryMock;

    /**
     * @var AthosCommerceLogger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var GetCronStatus
     */
    private $model;

    protected function setUp(): void
    {
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->responseFactoryMock = $this->createMock(CronStatusListInterfaceFactory::class);
        $this->itemFactoryMock = $this->createMock(CronStatusInterfaceFactory::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->model = new GetCronStatus(
            $this->collectionFactoryMock,
            $this->responseFactoryMock,
            $this->itemFactoryMock,
            $this->loggerMock
        );
    }

    public function testGetListReturnsTotalRecordsAndCronJobs(): void
    {
        $collection = $this->createMock(Collection::class);
        $response = $this->createMock(CronStatusListInterface::class);
        $cronItemOne = $this->createConfiguredCronItem('pending');
        $cronItemTwo = $this->createConfiguredCronItem();
        $scheduleOne = $this->createSchedule(
            52,
            'athoscommerce_task_execution',
            'pending',
            '',
            '2026-09-01 09:54:38',
            '2026-09-01 09:57:00',
            '',
            '',
            52
        );
        $scheduleTwo = $this->createSchedule(
            51,
            'athoscommerce_task_execution',
            'pending',
            '',
            '2026-09-01 09:54:38',
            '2026-09-01 09:56:00',
            '',
            '',
            51
        );
        $orderCalls = [];

        $filterCalls = [];

        $this->collectionFactoryMock->expects($this->once())->method('create')->willReturn($collection);
        $collection->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->willReturnCallback(
                function (string $field, $condition) use (&$filterCalls, $collection) {
                    $filterCalls[] = [$field, $condition];
                    return $collection;
                }
            );
        $collection->expects($this->once())->method('getSelect')->willReturn($this->createSelectMock());
        $collection->expects($this->once())->method('getSize')->willReturn(15);
        $collection->expects($this->exactly(2))
            ->method('setOrder')
            ->willReturnCallback(
                function (string $field, string $direction) use (&$orderCalls, $collection) {
                    $orderCalls[] = [$field, $direction];
                    return $collection;
                }
            );
        $collection->expects($this->once())->method('setPageSize')->with(15)->willReturnSelf();
        $collection->expects($this->once())->method('setCurPage')->with(1)->willReturnSelf();
        $collection->expects($this->once())->method('getItems')->willReturn([$scheduleOne, $scheduleTwo]);

        $this->itemFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($cronItemOne, $cronItemTwo);
        $this->responseFactoryMock->expects($this->once())->method('create')->willReturn($response);
        $this->loggerMock->expects($this->once())->method('debug');

        $response->expects($this->once())->method('setTotalRecords')->with(15)->willReturnSelf();
        $response->expects($this->once())
            ->method('setCronJobs')
            ->with([$cronItemOne, $cronItemTwo])
            ->willReturnSelf();

        $this->assertSame($response, $this->model->getList('all', 'pending', 1, 15));
        $this->assertSame(
            [
                ['scheduled_at', 'DESC'],
                ['schedule_id', 'DESC'],
            ],
            $orderCalls
        );
        $this->assertSame(
            [
                [
                    'job_code',
                    [
                        ['eq' => 'athoscommerce_task_execution'],
                        ['eq' => 'athoscommerce_live_indexing_discovery'],
                        ['eq' => 'athoscommerce_live_indexing_sync'],
                    ],
                ],
                ['status', 'pending'],
            ],
            $filterCalls
        );
    }

    public function testGetListAppliesSpecificJobCodeAndPagination(): void
    {
        $collection = $this->createMock(Collection::class);
        $response = $this->createMock(CronStatusListInterface::class);
        $cronItem = $this->createConfiguredCronItem('running');
        $schedule = $this->createSchedule(
            41,
            'athoscommerce_live_indexing_sync',
            'running',
            'working',
            '2026-09-01 09:54:38',
            '2026-09-01 09:58:00',
            '2026-09-01 09:58:05',
            '',
            41
        );
        $filterCalls = [];

        $this->collectionFactoryMock->expects($this->once())->method('create')->willReturn($collection);
        $collection->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->willReturnCallback(
                function (string $field, $condition) use (&$filterCalls, $collection) {
                    $filterCalls[] = [$field, $condition];
                    return $collection;
                }
            );
        $collection->expects($this->once())->method('getSelect')->willReturn($this->createSelectMock());
        $collection->expects($this->once())->method('getSize')->willReturn(6);
        $collection->method('setOrder')->willReturnSelf();
        $collection->expects($this->once())->method('setPageSize')->with(5)->willReturnSelf();
        $collection->expects($this->once())->method('setCurPage')->with(2)->willReturnSelf();
        $collection->expects($this->once())->method('getItems')->willReturn([$schedule]);

        $this->itemFactoryMock->expects($this->once())->method('create')->willReturn($cronItem);
        $this->responseFactoryMock->expects($this->once())->method('create')->willReturn($response);
        $this->loggerMock->expects($this->once())->method('debug');

        $response->expects($this->once())->method('setTotalRecords')->with(6)->willReturnSelf();
        $response->expects($this->once())->method('setCronJobs')->with([$cronItem])->willReturnSelf();

        $this->assertSame(
            $response,
            $this->model->getList('athoscommerce_live_indexing_sync', 'running', 2, 5)
        );
        $this->assertSame(
            [
                ['job_code', [['eq' => 'athoscommerce_live_indexing_sync']]],
                ['status', 'running'],
            ],
            $filterCalls
        );
    }

    private function createSchedule(
        ?int $scheduleId,
        ?string $jobCode,
        ?string $status,
        ?string $messages,
        ?string $createdAt,
        ?string $scheduledAt,
        ?string $executedAt,
        ?string $finishedAt,
        ?int $id
    ): Schedule {
        return $this->createConfiguredMock(
            Schedule::class,
            [
                'getScheduleId' => $scheduleId,
                'getJobCode' => $jobCode,
                'getStatus' => $status,
                'getMessages' => $messages,
                'getCreatedAt' => $createdAt,
                'getScheduledAt' => $scheduledAt,
                'getExecutedAt' => $executedAt,
                'getFinishedAt' => $finishedAt,
                'getId' => $id,
            ]
        );
    }

    private function createConfiguredCronItem(?string $status = null): CronStatusInterface
    {
        $cronItem = $this->createMock(CronStatusInterface::class);
        $cronItem->method('setScheduleId')->willReturnSelf();
        $cronItem->method('setJobCode')->willReturnSelf();
        $cronItem->method('setStatus')->willReturnSelf();
        $cronItem->method('setMessages')->willReturnSelf();
        $cronItem->method('setCreatedAt')->willReturnSelf();
        $cronItem->method('setScheduledAt')->willReturnSelf();
        $cronItem->method('setExecutedAt')->willReturnSelf();
        $cronItem->method('setFinishedAt')->willReturnSelf();
        $cronItem->method('getStatus')->willReturn($status);

        return $cronItem;
    }

    private function createSelectMock(): object
    {
        return new class {
            public function __toString(): string
            {
                return 'SELECT * FROM cron_schedule';
            }
        };
    }
}
