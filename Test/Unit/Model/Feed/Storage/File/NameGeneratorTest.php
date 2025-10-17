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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\Storage\File;

use Magento\Framework\Stdlib\DateTime\DateTime;
use AthosCommerce\Feed\Model\Feed\Storage\File\NameGenerator;

class NameGeneratorTest extends \PHPUnit\Framework\TestCase
{
    private $dateTimeMock;

    private $nameGenerator;

    public function setUp(): void
    {
        $this->dateTimeMock = $this->createMock(DateTime::class);
        $this->nameGenerator = new NameGenerator($this->dateTimeMock);
    }

    public function testGenerate()
    {
        $testDateTime = '2000-02-20 00:00:00';
        $testOption = 'test';
        $this->dateTimeMock->expects($this->once())
            ->method('gmtDate')
            ->willReturn($testDateTime);
        $this->assertSame(
            'athoscommerce_' . $testOption . '_2000_02_20_00_00_00',
            $this->nameGenerator->generate([$testOption])
        );
    }
}
