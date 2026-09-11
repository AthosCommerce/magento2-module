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
use AthosCommerce\Feed\Api\Data\CronStatusResponseInterface;
use AthosCommerce\Feed\Api\Data\CronStatusResponseInterfaceFactory;
use AthosCommerce\Feed\Model\Api\GetCronStatus;
use Magento\Cron\Model\ResourceModel\Schedule\Collection;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Cron\Model\Schedule;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\TestCase;

class GetCronStatusTest extends TestCase
{
    /**
     * @var CollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $collectionFactoryMock;

    /**
     * @var CronStatusResponseInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $responseFactoryMock;

    /**
     * @var CronStatusInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $itemFactoryMock;

    /**
     * @var DateTime|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dateTimeMock;

    /**
     * @var GetCronStatus
     */
    private $model;

    protected function setUp(): void
    {
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->responseFactoryMock = $this->createMock(CronStatusResponseInterfaceFactory::class);
        $this->itemFactoryMock = $this->createMock(CronStatusInterfaceFactory::class);
        $this->dateTimeMock = $this->createMock(DateTime::class);

        $this->model = new GetCronStatus(
            $this->collectionFactoryMock,
            $this->responseFactoryMock,
            $this->itemFactoryMock,
            $this->dateTimeMock
        );
    }

    public function testGetListBuildsCronSummaryAcrossConfiguredJobs(): void
    {
        $recentCollection = $this->createMock(Collection::class);
        $successCollection = $this->createMock(Collection::class);
        $response = $this->createMock(CronStatusResponseInterface::class);
        $scheduleOne = $this->createConfiguredMock(
            Schedule::class,
            [
                'getScheduleId' => 112815306,
                'getJobCode' => 'athoscommerce_task_execution',
                'getStatus' => 'pending',
                'getMessages' => '',
                'getCreatedAt' => '2026-07-09 08:50:07',
                'getScheduledAt' => '2026-07-09 08:53:00',
                'getExecutedAt' => null,
                'getFinishedAt' => null,
            ]
        );
        $scheduleTwo = $this->createConfiguredMock(
            Schedule::class,
            [
                'getScheduleId' => 112815305,
                'getJobCode' => 'athoscommerce_live_indexing_sync',
                'getStatus' => 'running',
                'getMessages' => 'working',
                'getCreatedAt' => '2026-07-09 08:49:07',
                'getScheduledAt' => '2026-07-09 08:52:30',
                'getExecutedAt' => '2026-07-09 08:52:31',
                'getFinishedAt' => null,
            ]
        );
        $scheduleThree = $this->createConfiguredMock(
            Schedule::class,
            [
                'getScheduleId' => 112814226,
                'getJobCode' => 'athoscommerce_live_indexing_discovery',
                'getStatus' => 'success',
                'getMessages' => '',
                'getCreatedAt' => '2026-07-09 08:48:09',
                'getScheduledAt' => '2026-07-09 08:50:00',
                'getExecutedAt' => '2026-07-09 08:51:06',
                'getFinishedAt' => '2026-07-09 08:51:06',
                'getId' => 112814226,
            ]
        );
        $cronItemOne = $this->createMock(CronStatusInterface::class);
        $cronItemTwo = $this->createMock(CronStatusInterface::class);
        $cronItemThree = $this->createMock(CronStatusInterface::class);
        $recentOrderCalls = [];
        $successFilterCalls = [];
        $successOrderCalls = [];

        $this->collectionFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($recentCollection, $successCollection);

        $jobCodeCondition = [
            ['eq' => 'athoscommerce_task_execution'],
            ['eq' => 'athoscommerce_live_indexing_discovery'],
            ['eq' => 'athoscommerce_live_indexing_sync'],
        ];

        $recentCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('job_code', $jobCodeCondition)
            ->willReturnSelf();
        $recentCollection->expects($this->exactly(2))
            ->method('setOrder')
            ->willReturnCallback(
                function (string $field, string $direction) use (&$recentOrderCalls, $recentCollection) {
                    $recentOrderCalls[] = [$field, $direction];
                    return $recentCollection;
                }
            );
        $recentCollection->expects($this->once())->method('setPageSize')->with(3)->willReturnSelf();
        $recentCollection->expects($this->once())->method('setCurPage')->with(1)->willReturnSelf();
        $recentCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([$scheduleOne, $scheduleTwo, $scheduleThree]);

        $successCollection->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->willReturnCallback(function (string $field, $condition) use (&$successFilterCalls, $successCollection) {
                $successFilterCalls[] = [$field, $condition];
                return $successCollection;
            });
        $successCollection->expects($this->exactly(2))
            ->method('setOrder')
            ->willReturnCallback(
                function (string $field, string $direction) use (&$successOrderCalls, $successCollection) {
                    $successOrderCalls[] = [$field, $direction];
                    return $successCollection;
                }
            );
        $successCollection->expects($this->once())->method('setPageSize')->with(1)->willReturnSelf();
        $successCollection->expects($this->once())->method('setCurPage')->with(1)->willReturnSelf();
        $successCollection->expects($this->once())->method('getFirstItem')->willReturn($scheduleThree);

        $this->itemFactoryMock->expects($this->exactly(3))
            ->method('create')
            ->willReturnOnConsecutiveCalls($cronItemOne, $cronItemTwo, $cronItemThree);

        $cronItemOne->method('setScheduleId')->willReturnSelf();
        $cronItemOne->method('setJobCode')->willReturnSelf();
        $cronItemOne->method('setStatus')->willReturnSelf();
        $cronItemOne->method('setMessages')->willReturnSelf();
        $cronItemOne->method('setCreatedAt')->willReturnSelf();
        $cronItemOne->method('setScheduledAt')->willReturnSelf();
        $cronItemOne->method('setExecutedAt')->willReturnSelf();
        $cronItemOne->method('setFinishedAt')->willReturnSelf();
        $cronItemOne->expects($this->once())->method('getStatus')->willReturn('pending');

        $cronItemTwo->method('setScheduleId')->willReturnSelf();
        $cronItemTwo->method('setJobCode')->willReturnSelf();
        $cronItemTwo->method('setStatus')->willReturnSelf();
        $cronItemTwo->method('setMessages')->willReturnSelf();
        $cronItemTwo->method('setCreatedAt')->willReturnSelf();
        $cronItemTwo->method('setScheduledAt')->willReturnSelf();
        $cronItemTwo->method('setExecutedAt')->willReturnSelf();
        $cronItemTwo->method('setFinishedAt')->willReturnSelf();

        $cronItemThree->method('setScheduleId')->willReturnSelf();
        $cronItemThree->method('setJobCode')->willReturnSelf();
        $cronItemThree->method('setStatus')->willReturnSelf();
        $cronItemThree->method('setMessages')->willReturnSelf();
        $cronItemThree->method('setCreatedAt')->willReturnSelf();
        $cronItemThree->method('setScheduledAt')->willReturnSelf();
        $cronItemThree->method('setExecutedAt')->willReturnSelf();
        $cronItemThree->method('setFinishedAt')->willReturnSelf();

        $this->responseFactoryMock->expects($this->once())->method('create')->willReturn($response);
        $this->dateTimeMock->expects($this->once())->method('gmtDate')->willReturn('2026-07-09 08:55:00');

        $response->expects($this->once())->method('setIsRunning')->with(true)->willReturnSelf();
        $response->expects($this->once())
            ->method('setLastSuccessAt')
            ->with('2026-07-09 08:51:06')
            ->willReturnSelf();
        $response->expects($this->once())->method('setLastStatus')->with('pending')->willReturnSelf();
        $response->expects($this->once())
            ->method('setCronJobs')
            ->with([$cronItemOne, $cronItemTwo, $cronItemThree])
            ->willReturnSelf();

        $this->assertSame($response, $this->model->getList());
        $this->assertSame(
            [
                ['scheduled_at', 'DESC'],
                ['schedule_id', 'DESC'],
            ],
            $recentOrderCalls
        );
        $this->assertSame(
            [
                ['job_code', $jobCodeCondition],
                ['status', 'success'],
            ],
            $successFilterCalls
        );
        $this->assertSame(
            [
                ['scheduled_at', 'DESC'],
                ['schedule_id', 'DESC'],
            ],
            $successOrderCalls
        );
    }

    public function testGetListReturnsStoppedStateWhenNoSuccessfulRunExists(): void
    {
        $recentCollection = $this->createMock(Collection::class);
        $successCollection = $this->createMock(Collection::class);
        $response = $this->createMock(CronStatusResponseInterface::class);
        $schedule = $this->createConfiguredMock(
            Schedule::class,
            [
                'getScheduleId' => 1,
                'getJobCode' => 'athoscommerce_task_execution',
                'getStatus' => 'error',
                'getMessages' => 'failed',
                'getCreatedAt' => '2026-07-09 08:50:07',
                'getScheduledAt' => '2026-07-09 08:53:00',
                'getExecutedAt' => '2026-07-09 08:53:02',
                'getFinishedAt' => '2026-07-09 08:53:03',
            ]
        );
        $emptySuccessSchedule = $this->createConfiguredMock(
            Schedule::class,
            [
                'getId' => null,
            ]
        );
        $cronItem = $this->createMock(CronStatusInterface::class);

        $this->collectionFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($recentCollection, $successCollection);

        $recentCollection->method('addFieldToFilter')->willReturnSelf();
        $recentCollection->method('setOrder')->willReturnSelf();
        $recentCollection->method('setPageSize')->willReturnSelf();
        $recentCollection->method('setCurPage')->willReturnSelf();
        $recentCollection->expects($this->once())->method('getItems')->willReturn([$schedule]);

        $successCollection->method('addFieldToFilter')->willReturnSelf();
        $successCollection->method('setOrder')->willReturnSelf();
        $successCollection->method('setPageSize')->willReturnSelf();
        $successCollection->method('setCurPage')->willReturnSelf();
        $successCollection->expects($this->once())->method('getFirstItem')->willReturn($emptySuccessSchedule);

        $this->itemFactoryMock->expects($this->once())->method('create')->willReturn($cronItem);
        $cronItem->method('setScheduleId')->willReturnSelf();
        $cronItem->method('setJobCode')->willReturnSelf();
        $cronItem->method('setStatus')->willReturnSelf();
        $cronItem->method('setMessages')->willReturnSelf();
        $cronItem->method('setCreatedAt')->willReturnSelf();
        $cronItem->method('setScheduledAt')->willReturnSelf();
        $cronItem->method('setExecutedAt')->willReturnSelf();
        $cronItem->method('setFinishedAt')->willReturnSelf();
        $cronItem->expects($this->once())->method('getStatus')->willReturn('error');

        $this->responseFactoryMock->expects($this->once())->method('create')->willReturn($response);
        $this->dateTimeMock->expects($this->never())->method('gmtDate');

        $response->expects($this->once())->method('setIsRunning')->with(false)->willReturnSelf();
        $response->expects($this->once())->method('setLastSuccessAt')->with(null)->willReturnSelf();
        $response->expects($this->once())->method('setLastStatus')->with('error')->willReturnSelf();
        $response->expects($this->once())->method('setCronJobs')->with([$cronItem])->willReturnSelf();

        $this->assertSame($response, $this->model->getList());
    }
}
