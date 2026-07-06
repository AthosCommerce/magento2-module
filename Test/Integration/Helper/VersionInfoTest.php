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

namespace AthosCommerce\Feed\Test\Integration\Helper;

use AthosCommerce\Feed\Helper\VersionInfo;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class VersionInfoTest extends TestCase
{
    /**
     * @var VersionInfo
     */
    private $versionInfo;

    protected function setUp(): void
    {
        $this->versionInfo = Bootstrap::getObjectManager()->get(VersionInfo::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetVersion(): void
    {
        $result = $this->versionInfo->getVersion();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $row = $result[0];

        $this->assertArrayHasKey('extensionVersion', $row);
        $this->assertArrayHasKey('magento', $row);
        $this->assertArrayHasKey('memLimit', $row);
        $this->assertArrayHasKey('OSType', $row);
        $this->assertArrayHasKey('OSVersion', $row);
        $this->assertArrayHasKey('maxExecutionTime', $row);
        $this->assertArrayHasKey('magentoName', $row);
        $this->assertArrayHasKey('magentoVersion', $row);
        $this->assertArrayHasKey('magentoEdition', $row);
        $this->assertArrayHasKey('magentoRootPath', $row);
        $this->assertArrayHasKey('magentoLogPath', $row);

        $this->assertNotEmpty($row['magentoName']);
        $this->assertNotEmpty($row['magentoVersion']);
        $this->assertNotEmpty($row['magentoRootPath']);
        $this->assertNotEmpty($row['magentoLogPath']);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetMemoryLimit(): void
    {
        $memoryLimit = $this->versionInfo->getMemoryLimit();
        $this->assertNotEmpty($memoryLimit);
    }
}
