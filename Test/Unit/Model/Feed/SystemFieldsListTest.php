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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed;

use AthosCommerce\Feed\Model\Feed\SystemFieldsList;

class SystemFieldsListTest extends \PHPUnit\Framework\TestCase
{
    private $testData = ['test'];

    private $systemFieldsList;

    public function setUp(): void
    {
        $this->systemFieldsList = new SystemFieldsList($this->testData);
    }

    public function testGet()
    {
        $this->assertSame($this->testData, $this->systemFieldsList->get());
    }

    public function testAdd()
    {
        $testField = 'test1';
        $this->systemFieldsList->add($testField);
        $this->assertSame(array_merge($this->testData, [$testField]), $this->systemFieldsList->get());
    }

    public function testIsSystem()
    {
        $this->assertSame(true, $this->systemFieldsList->isSystem('test'));
    }
}
