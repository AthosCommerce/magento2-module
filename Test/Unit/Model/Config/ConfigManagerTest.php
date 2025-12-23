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

namespace AthosCommerce\Feed\Test\Unit\Model\Config;

use AthosCommerce\Module\Api\Data\ConfigItemInterface;
use AthosCommerce\Module\Api\Data\ConfigBulkResultInterface;
use AthosCommerce\Module\Model\Config\ConfigManager;
use AthosCommerce\Module\Model\Validator\AthosCommerceConfigValidator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class ConfigManagerTest extends TestCase
{
    /** @var WriterInterface|MockObject */
    private $configWriter;

    /** @var ScopeConfigInterface|MockObject */
    private $scopeConfig;

    /** @var AthosCommerceConfigValidator|MockObject */
    private $validator;

    /** @var AthosCommerceLogger|MockObject */
    private $logger;

    /** @var ConfigManager */
    private $manager;

    protected function setUp(): void
    {
        $this->configWriter = $this->createMock(WriterInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->validator = $this->createMock(AthosCommerceConfigValidator::class);
        $this->logger = $this->createMock(AthosCommerceLogger::class);

        $this->manager = new ConfigManager(
            $this->configWriter,
            $this->scopeConfig,
            $this->validator,
            $this->logger
        );
    }

    public function testUpdateBulkSuccess(): void
    {
        $item1 = $this->createMock(ConfigItemInterface::class);
        $item2 = $this->createMock(ConfigItemInterface::class);

        $item1->method('getPath')->willReturn('athoscommerce/configuration/key1');
        $item1->method('getValue')->willReturn('value1');
        $item1->method('getScope')->willReturn('default');
        $item1->method('getScopeId')->willReturn(0);

        $item2->method('getPath')->willReturn('athoscommerce/configuration/key2');
        $item2->method('getValue')->willReturn('value2');
        $item2->method('getScope')->willReturn('websites');
        $item2->method('getScopeId')->willReturn(1);

        // validator passes both items
        $this->validator->expects($this->exactly(2))
            ->method('validatePath')
            ->withConsecutive(
                ['athoscommerce/configuration/key1', 'value1'],
                ['athoscommerce/configuration/key2', 'value2']
            );

        $this->configWriter
            ->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive(
                ['athoscommerce/configuration/key1', 'value1', 'default', 0],
                ['athoscommerce/configuration/key2', 'value2', 'websites', 1]
            );

        $result = $this->manager->updateBulk([$item1, $item2]);

        $this->assertEquals(2, $result->getTotal());
        $this->assertEquals(2, $result->getSaved());
        $this->assertEquals(0, $result->getFailed());

        $this->assertCount(2, $result->getResults());
    }

    public function testUpdateBulkWithFailures(): void
    {
        $item1 = $this->createMock(ConfigItemInterface::class);
        $item2 = $this->createMock(ConfigItemInterface::class);

        $item1->method('getPath')->willReturn('athoscommerce/configuration/key1');
        $item1->method('getValue')->willReturn('value1');
        $item1->method('getScope')->willReturn('default');
        $item1->method('getScopeId')->willReturn(0);

        $item2->method('getPath')->willReturn('athoscommerce/configuration/key2');
        $item2->method('getValue')->willReturn('value2');
        $item2->method('getScope')->willReturn('default');
        $item2->method('getScopeId')->willReturn(0);

        // first passes, second fails
        $this->validator->method('validatePath')
            ->willReturnCallback(function ($path) {
                if ($path === 'athoscommerce/configuration/key2') {
                    throw new LocalizedException(__('Invalid config path'));
                }

                return true;
            });

        $this->configWriter
            ->expects($this->once())
            ->method('save')
            ->with('athoscommerce/configuration/key1', 'value1', 'default', 0);

        $result = $this->manager->updateBulk([$item1, $item2]);

        $this->assertEquals(2, $result->getTotal());
        $this->assertEquals(1, $result->getSaved());
        $this->assertEquals(1, $result->getFailed());

        $this->assertCount(2, $result->getResults());

        $this->assertFalse($result->getResults()[1]['success']);
        $this->assertEquals('athoscommerce/configuration/key2', $result->getResults()[1]['path']);
    }
}
