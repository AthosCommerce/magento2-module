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

namespace AthosCommerce\Feed\Test\Integration\Api;

use AthosCommerce\Feed\Api\Data\ConfigItemInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use AthosCommerce\Feed\Api\ConfigUpdateInterface;
use AthosCommerce\Feed\Api\Data\ConfigItemInterfaceFactory;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigUpdateInterfaceTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var ConfigUpdateInterface
     */
    private $configUpdate;
    /**
     * @var ConfigItemInterfaceFactory
     */
    private $configItemFactory;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->configUpdate = $this->objectManager->get(ConfigUpdateInterface::class);
        $this->configItemFactory = $this->objectManager->get(ConfigItemInterfaceFactory::class);
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        parent::setUp();
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testExecute(): void
    {
        $store = $this->storeManager->getDefaultStoreView();

        $payload = $this->configItemFactory->create();
        $payload->setStoreCode($store->getCode());

        $payload->setEnableLiveIndexing(1);
        /** @var \AthosCommerce\Feed\Api\Data\ConfigUpdateResponseInterface $response */
        $response = $this->configUpdate->update($payload);

        $this->assertInstanceOf(
            \AthosCommerce\Feed\Api\Data\ConfigUpdateResponseInterface::class,
            $response
        );
        $this->assertTrue($response->getSuccess());
        $this->assertSame($store->getCode(), $response->getStoreCode());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testMissingStoreCodeThrowsException(): void
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);

        $payload = $this->configItemFactory->create();
        $this->configUpdate->update($payload);
    }
}
