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
        /** @var \AthosCommerce\Feed\Api\Data\ConfigInfoResponseInterface $response */
        $response = $this->getConfigInfo->get();

        $this->assertInstanceOf(
            \AthosCommerce\Feed\Api\Data\ConfigInfoResponseInterface::class,
            $response
        );

        $this->assertTrue($response->getSuccess());

        $stores = $response->getStores();

        $this->assertIsArray($stores);
        $this->assertNotEmpty($stores);
        $store = $stores[0];

        $this->assertIsArray($store);
        $this->assertArrayHasKey('storeId', $store);
        $this->assertArrayHasKey('storeCode', $store);
        $this->assertArrayHasKey('enableLiveIndexing', $store);

        $this->assertArrayNotHasKey('store_id', $store);
        $this->assertArrayNotHasKey('enable_live_indexing', $store);
    }
}
