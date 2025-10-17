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

namespace AthosCommerce\Feed\Test\Integration\Model\Feed\DataProvider\Stock;

use Magento\Framework\Module\Manager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\LegacyStockProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\MsiStockProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\StockResolverInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StockResolverInterfaceTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var Manager
     */
    private $moduleManager;
    /**
     * @var StockResolverInterface
     */
    private $resolver;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->moduleManager = $this->objectManager->get(Manager::class);
        $this->resolver = $this->objectManager->get(StockResolverInterface::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @throws \Exception
     */
    public function testResolveMsi() : void
    {
        if (!$this->isMsiEnabled()) {
            $this->markTestSkipped('MSI is disabled');
        }

        $this->assertInstanceOf(MsiStockProvider::class, $this->resolver->resolve());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @throws \Exception
     */
    public function testResolveLegacy() : void
    {
        if ($this->isMsiEnabled()) {
            $this->markTestSkipped('MSI is enabled');
        }

        $this->assertInstanceOf(LegacyStockProvider::class, $this->resolver->resolve());
    }

    /**
     * @return bool
     */
    private function isMsiEnabled() : bool
    {
        return $this->moduleManager->isEnabled('Magento_Inventory');
    }
}
