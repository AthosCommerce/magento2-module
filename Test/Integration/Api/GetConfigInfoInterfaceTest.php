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
use AthosCommerce\Feed\Api\GetConfigInfoInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetConfigInfoInterfaceTest extends TestCase
{
    /**
     * @var GetConfigInfoInterface
     */
    private $getConfigInfo;

    protected function setUp(): void
    {
        $this->getConfigInfo = Bootstrap::getObjectManager()->get(GetConfigInfoInterface::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/config.php
     */
    public function testExecute(): void
    {
        $response = $this->getConfigInfo->get();

        $this->assertTrue($response['data']['success']);
        $this->assertArrayHasKey('stores', $response['data']['results']);

        $stores = $response['data']['results']['stores'];
        $this->assertNotEmpty($stores);

        $store = $stores[0];

        $this->assertEquals('test', $store['storeCode']);
        $this->assertEquals(1, $store['enableLiveIndexing']);
    }
}
