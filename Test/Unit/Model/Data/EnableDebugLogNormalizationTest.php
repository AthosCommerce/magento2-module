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

namespace AthosCommerce\Feed\Test\Unit\Model\Data;

use AthosCommerce\Feed\Api\Data\ConfigItemInterface;
use AthosCommerce\Feed\Api\Data\StoreConfigInterface;
use AthosCommerce\Feed\Model\Data\ConfigItem;
use AthosCommerce\Feed\Model\Data\StoreConfig;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use PHPUnit\Framework\TestCase;

class EnableDebugLogNormalizationTest extends TestCase
{
    /** @var ExtensionAttributesFactory */
    private $extensionAttributesFactory;

    /** @var AttributeValueFactory */
    private $attributeValueFactory;

    /** @var Context */
    private $context;

    /** @var Registry */
    private $registry;

    protected function setUp(): void
    {
        $this->extensionAttributesFactory = $this->createMock(ExtensionAttributesFactory::class);
        $this->attributeValueFactory = $this->createMock(AttributeValueFactory::class);
        $this->context = $this->createMock(Context::class);
        $this->registry = $this->createMock(Registry::class);
    }

    /**
     * @dataProvider validBooleanValueProvider
     *
     * @param mixed $input
     * @param mixed $expected
     * @return void
     */
    public function testConfigItemNormalizesRecognizedBooleanValues($input, $expected): void
    {
        $configItem = $this->createConfigItem();

        $configItem->setEnableDebugLog($input);

        $this->assertSame($expected, $configItem->__toArray()[ConfigItemInterface::ENABLE_DEBUG_LOG]);
    }

    public function testConfigItemPreservesInvalidBooleanStringsForValidation(): void
    {
        $configItem = $this->createConfigItem();

        $configItem->setEnableDebugLog('garbage');

        $this->assertSame('garbage', $configItem->__toArray()[ConfigItemInterface::ENABLE_DEBUG_LOG]);
    }

    /**
     * @dataProvider validBooleanValueProvider
     *
     * @param mixed $input
     * @param mixed $expected
     * @return void
     */
    public function testStoreConfigNormalizesRecognizedBooleanValues($input, $expected): void
    {
        $storeConfig = $this->createStoreConfig();

        $storeConfig->setEnableDebugLog($input);

        $this->assertSame($expected, $storeConfig->getData(StoreConfigInterface::ENABLE_DEBUG_LOG));
    }

    public function testStoreConfigPreservesInvalidBooleanStringsForValidation(): void
    {
        $storeConfig = $this->createStoreConfig();

        $storeConfig->setEnableDebugLog('garbage');

        $this->assertSame('garbage', $storeConfig->getData(StoreConfigInterface::ENABLE_DEBUG_LOG));
    }

    /**
     * @return array
     */
    public static function validBooleanValueProvider(): array
    {
        return [
            'null' => [null, null],
            'true bool' => [true, true],
            'false bool' => [false, false],
            'int zero' => [0, false],
            'int one' => [1, true],
            'string zero' => ['0', false],
            'string one' => ['1', true],
            'string true' => ['true', true],
            'string false' => ['false', false],
            'trimmed true' => ['  TRUE  ', true],
            'trimmed false' => ['  false  ', false],
        ];
    }

    private function createConfigItem(): ConfigItem
    {
        return new ConfigItem(
            $this->extensionAttributesFactory,
            $this->attributeValueFactory
        );
    }

    private function createStoreConfig(): StoreConfig
    {
        return new StoreConfig(
            $this->context,
            $this->registry,
            $this->extensionAttributesFactory,
            $this->attributeValueFactory
        );
    }
}
