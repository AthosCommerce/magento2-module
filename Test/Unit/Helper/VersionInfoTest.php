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

namespace AthosCommerce\Feed\Test\Unit\Helper;

use AthosCommerce\Feed\Helper\VersionInfo;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\Dir;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VersionInfoTest extends TestCase
{
    /**
     * @var ProductMetadataInterface|MockObject
     */
    private $productMetadataMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var Dir|MockObject
     */
    private $moduleDirMock;

    /**
     * @var VersionInfo
     */
    private $versionInfo;

    protected function setUp(): void
    {
        $this->productMetadataMock = $this->createMock(ProductMetadataInterface::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->moduleDirMock = $this->createMock(Dir::class);

        $this->versionInfo = new VersionInfo(
            $this->productMetadataMock,
            $this->directoryListMock,
            $this->moduleDirMock
        );
    }

    public function testGetModuleDirectory(): void
    {
        $this->moduleDirMock->expects($this->once())
            ->method('getDir')
            ->with('AthosCommerce_Feed')
            ->willReturn('/magento-root/vendor/athoscommerce/magento2-module');

        $this->assertSame(
            '/magento-root/vendor/athoscommerce/magento2-module',
            $this->versionInfo->getModuleDirectory('AthosCommerce_Feed')
        );
    }

    public function testGetMemoryLimitReturnsBytesForMegabytes(): void
    {
        $result = $this->versionInfo->getMemoryLimit();
        $this->assertIsNumeric($result);
    }

    public function testGetVersionReturnsExpectedStructure(): void
    {
        $this->productMetadataMock->method('getName')->willReturn('Magento');
        $this->productMetadataMock->method('getVersion')->willReturn('2.4.8');
        $this->productMetadataMock->method('getEdition')->willReturn('Enterprise');

        $this->directoryListMock->expects($this->once())
            ->method('getRoot')
            ->willReturn('/var/www/html');

        $this->directoryListMock->expects($this->once())
            ->method('getPath')
            ->with(DirectoryList::LOG)
            ->willReturn('/var/www/html/var/log');

        $this->moduleDirMock->expects($this->once())
            ->method('getDir')
            ->with(VersionInfo::MODULE_NAME)
            ->willReturn('/path/that/does/not/exist');

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

        $this->assertSame('Magento', $row['magentoName']);
        $this->assertSame('2.4.8', $row['magentoVersion']);
        $this->assertSame('Enterprise', $row['magentoEdition']);
        $this->assertSame('/var/www/html', $row['magentoRootPath']);
        $this->assertSame('/var/www/html/var/log', $row['magentoLogPath']);
    }

    public function testGetVersionFromComposerReturnsUnavailableWhenComposerFileCannotBeRead(): void
    {
        $this->moduleDirMock->expects($this->once())
            ->method('getDir')
            ->with(VersionInfo::MODULE_NAME)
            ->willReturn('/path/that/does/not/exist');

        $this->assertSame('unavailable', $this->versionInfo->getVersionFromComposer());
    }
}
