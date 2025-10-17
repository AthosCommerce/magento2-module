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

use AthosCommerce\Feed\Api\AppConfigInterface;
use AthosCommerce\Feed\Model\Feed\CollectionConfig;

class CollectionConfigTest extends \PHPUnit\Framework\TestCase
{
    private $appConfigMock;

    private $collectionConfig;

    public function setUp(): void
    {
        $this->appConfigMock = $this->createMock(AppConfigInterface::class);
        $this->collectionConfig = new CollectionConfig($this->appConfigMock);
    }

    public function testGetPageSize()
    {
        $pageSize = 1500;
        $this->appConfigMock->expects($this->once())
            ->method('getValue')
            ->with(CollectionConfig::PAGE_SIZE_CONFIG_PATH)
            ->willReturn($pageSize);

        $this->assertSame($pageSize, $this->collectionConfig->getPageSize());
    }
}
