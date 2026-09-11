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

use AthosCommerce\Feed\Api\Data\ApplicationLogResponseInterface;
use AthosCommerce\Feed\Api\Data\ApplicationLogResponseInterfaceFactory;
use AthosCommerce\Feed\Helper\LogInfo;
use AthosCommerce\Feed\Model\Api\GetApplicationLog;
use PHPUnit\Framework\TestCase;

class GetApplicationLogTest extends TestCase
{
    /**
     * @var LogInfo|\PHPUnit\Framework\MockObject\MockObject
     */
    private $helperMock;

    /**
     * @var ApplicationLogResponseInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $responseFactoryMock;

    /**
     * @var ApplicationLogResponseInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $responseMock;

    /**
     * @var GetApplicationLog
     */
    private $model;

    protected function setUp(): void
    {
        $this->helperMock = $this->createMock(LogInfo::class);
        $this->responseFactoryMock = $this->createMock(ApplicationLogResponseInterfaceFactory::class);
        $this->responseMock = $this->createMock(ApplicationLogResponseInterface::class);

        $this->responseFactoryMock->method('create')->willReturn($this->responseMock);
        $this->responseMock->method('setLines')->willReturnSelf();
        $this->responseMock->method('setContent')->willReturnSelf();
        $this->responseMock->method('setCompressed')->willReturnSelf();

        $this->model = new GetApplicationLog($this->helperMock, $this->responseFactoryMock);
    }

    public function testGetExtensionLogReturnsJsonFriendlyArrayOfLines(): void
    {
        $this->helperMock->expects($this->once())
            ->method('getExtensionLogFile')
            ->with(false, 1, 0, 0, '', '', '')
            ->willReturn("Line 1\nLine 2");

        $this->responseFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->responseMock);
        $this->responseMock->expects($this->once())->method('setCompressed')->with(false)->willReturnSelf();
        $this->responseMock->expects($this->once())->method('setLines')->with(['Line 1', 'Line 2'])->willReturnSelf();
        $this->responseMock->expects($this->once())->method('setContent')->with(null)->willReturnSelf();

        $result = $this->model->getExtensionLog(false, 1);

        $this->assertSame($this->responseMock, $result);
    }

    public function testGetExtensionLogReturnsEmptyArrayForEmptyContent(): void
    {
        $this->helperMock->expects($this->once())
            ->method('getExtensionLogFile')
            ->with(false, 100, 0, 0, '', '', '')
            ->willReturn('');

        $this->responseFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->responseMock);
        $this->responseMock->expects($this->once())->method('setCompressed')->with(false)->willReturnSelf();
        $this->responseMock->expects($this->once())->method('setLines')->with([])->willReturnSelf();
        $this->responseMock->expects($this->once())->method('setContent')->with(null)->willReturnSelf();

        $result = $this->model->getExtensionLog();

        $this->assertSame($this->responseMock, $result);
    }

    public function testGetExtensionLogReturnsCompressedContentInDto(): void
    {
        $this->helperMock->expects($this->once())
            ->method('getExtensionLogFile')
            ->with(true, 100, 0, 0, '', '', '')
            ->willReturn('compressed-payload');

        $this->responseFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->responseMock);
        $this->responseMock->expects($this->once())->method('setCompressed')->with(true)->willReturnSelf();
        $this->responseMock->expects($this->once())->method('setLines')->with([])->willReturnSelf();
        $this->responseMock->expects($this->once())->method('setContent')->with('compressed-payload')->willReturnSelf();

        $result = $this->model->getExtensionLog(true);

        $this->assertSame($this->responseMock, $result);
    }

    public function testGetCronLogForwardsArgumentsAndReturnsArrayOfLines(): void
    {
        $this->helperMock->expects($this->once())
            ->method('getCronLogFile')
            ->with(false, 25, 2, 5, 'ERROR', '2026-09-10', '2026-09-11')
            ->willReturn("Line A\nLine B");

        $this->responseFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->responseMock);
        $this->responseMock->expects($this->once())->method('setCompressed')->with(false)->willReturnSelf();
        $this->responseMock->expects($this->once())->method('setLines')->with(['Line A', 'Line B'])->willReturnSelf();
        $this->responseMock->expects($this->once())->method('setContent')->with(null)->willReturnSelf();

        $result = $this->model->getCronLog(false, 25, 2, 5, 'ERROR', '2026-09-10', '2026-09-11');

        $this->assertSame($this->responseMock, $result);
    }

    public function testGetExtensionErrorLogReturnsCompressedContentInDto(): void
    {
        $this->helperMock->expects($this->once())
            ->method('getExtensionErrorLogFile')
            ->with(true, 12, 0, 0, 'timeout', '', '')
            ->willReturn('compressed-error-payload');

        $this->responseFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->responseMock);
        $this->responseMock->expects($this->once())->method('setCompressed')->with(true)->willReturnSelf();
        $this->responseMock->expects($this->once())->method('setLines')->with([])->willReturnSelf();
        $this->responseMock->expects($this->once())->method('setContent')->with('compressed-error-payload')->willReturnSelf();

        $result = $this->model->getExtensionErrorLog(true, 12, 0, 0, 'timeout');

        $this->assertSame($this->responseMock, $result);
    }

    public function testGetExtensionDebugLogReturnsEmptyArrayForEmptyContent(): void
    {
        $this->helperMock->expects($this->once())
            ->method('getExtensionDebugLogFile')
            ->with(false, 100, 0, 0, '', '', '')
            ->willReturn('');

        $this->responseFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->responseMock);
        $this->responseMock->expects($this->once())->method('setCompressed')->with(false)->willReturnSelf();
        $this->responseMock->expects($this->once())->method('setLines')->with([])->willReturnSelf();
        $this->responseMock->expects($this->once())->method('setContent')->with(null)->willReturnSelf();

        $result = $this->model->getExtensionDebugLog();

        $this->assertSame($this->responseMock, $result);
    }

    public function testClearExtensionInfoLogDelegatesToHelper(): void
    {
        $this->helperMock->expects($this->once())
            ->method('deleteExtensionLogFile')
            ->willReturn(true);

        $this->assertTrue($this->model->clearExtensionInfoLog());
    }
}
