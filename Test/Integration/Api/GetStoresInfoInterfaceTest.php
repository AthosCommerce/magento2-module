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

use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use AthosCommerce\Feed\Api\GetStoresInfoInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetStoresInfoInterfaceTest extends TestCase
{
    /**
     * @var GetStoresInfoInterface
     */
    private $getStoresInfo;

    protected function setUp(): void
    {
        $this->getStoresInfo = Bootstrap::getObjectManager()->get(GetStoresInfoInterface::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/store.php
     */
    public function testExecute() : void
    {
        $storesInfo = $this->getStoresInfo->getAsHtml();
        $this->assertStoreCodeInResult($storesInfo, 'test', 'Test Store');
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/store.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/second_store.php
     */
    public function testExecuteWithMultiStore() : void
    {
        $storesInfo = $this->getStoresInfo->getAsHtml();
        $this->assertStoreCodeInResult($storesInfo, 'test', 'Test Store');
        $this->assertStoreCodeInResult($storesInfo, 'fixture_second_store', 'Fixture Store');
    }

    /**
     * @param string $result
     * @param string $storeCode
     * @param string $name
     */
    private function assertStoreCodeInResult(string $result, string $storeCode, string $name) : void
    {
        $substr = "<li>$name - $storeCode</li>";
        $this->assertEquals(1, substr_count($result, $substr));
    }
}
