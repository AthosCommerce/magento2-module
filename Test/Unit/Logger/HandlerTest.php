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

namespace AthosCommerce\Feed\Test\Unit\Logger;

use AthosCommerce\Feed\Logger\Handler;
use AthosCommerce\Feed\Model\Config as ConfigModel;
use Magento\Framework\Filesystem\DriverInterface;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class TestableHandler extends Handler
{
    public function readLoggerType(): int
    {
        return $this->loggerType;
    }
}

class HandlerTest extends TestCase
{
    public function testConstructSetsDebugLoggerTypeWhenDebugEnabled(): void
    {
        $configModelMock = $this->createMock(ConfigModel::class);
        $filesystemMock = $this->createMock(DriverInterface::class);

        $configModelMock->expects($this->once())
            ->method('isDebugLogEnabled')
            ->willReturn(true);

        $handler = new TestableHandler($configModelMock, $filesystemMock);

        $this->assertSame(Logger::DEBUG, $handler->readLoggerType());
    }

    public function testConstructSetsInfoLoggerTypeWhenDebugDisabled(): void
    {
        $configModelMock = $this->createMock(ConfigModel::class);
        $filesystemMock = $this->createMock(DriverInterface::class);

        $configModelMock->expects($this->once())
            ->method('isDebugLogEnabled')
            ->willReturn(false);

        $handler = new TestableHandler($configModelMock, $filesystemMock);

        $this->assertSame(Logger::INFO, $handler->readLoggerType());
    }
}
