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

namespace AthosCommerce\Feed\Test\Unit\Model\Metric\MetricProvider;

use Magento\Framework\Stdlib\DateTime\DateTime;
use AthosCommerce\Feed\Model\Metric\MetricProvider\TimeProvider;

class TimeProviderTest extends \PHPUnit\Framework\TestCase
{
    private $dateTimeMock;

    private $timeProvider;

    public function setUp(): void
    {
        $this->dateTimeMock = $this->createMock(DateTime::class);
        $this->timeProvider = new TimeProvider($this->dateTimeMock);
    }

    public function testGetMetrics()
    {
        $currentTime = '1999-12-31 23:59:59';
        $this->dateTimeMock->expects($this->once())
            ->method('gmtDate')
            ->willReturn($currentTime);

        $this->assertSame(['date' => $currentTime], $this->timeProvider->getMetrics([], []));
    }
}
