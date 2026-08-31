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

use AthosCommerce\Feed\Api\GetCustomersInterface;
use AthosCommerce\Feed\Exception\ValidationException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class GetCustomersInterfaceTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var GetCustomersInterface
     */
    private $getCustomers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->getCustomers = $this->objectManager->get(GetCustomersInterface::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/customer.php
     */
    public function testGetListReturnsPagedCustomersWithMetadata(): void
    {
        $result = $this->getCustomers->getList('2000-01-01', 1, 1);

        $this->assertCount(1, $result->getCustomers());
        $this->assertSame(1, $result->getCurrentSize());
        $this->assertSame(1, $result->getPageSize());
        $this->assertSame(1, $result->getTotalCount());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/customer.php
     */
    public function testGetListReturnsEmptyPageWhenRequestedPageIsOutOfRange(): void
    {
        $result = $this->getCustomers->getList('2000-01-01', 2, 1);

        $this->assertSame([], $result->getCustomers());
        $this->assertSame(0, $result->getCurrentSize());
        $this->assertSame(1, $result->getPageSize());
        $this->assertSame(1, $result->getTotalCount());
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetListRejectsInvalidDateRangeWithFieldDetails(): void
    {
        try {
            $this->getCustomers->getList('All', 1, 1);
            $this->fail('Expected validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(400, $exception->getCode());
            $this->assertSame('dateRange', $exception->getDetails()[0]['fieldName']);
            $this->assertSame('All', $exception->getDetails()[0]['fieldValue']);
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store athoscommerce/indexing/api_customers_max_page_size 10
     */
    public function testGetListRejectsPageSizeOverConfiguredMaximum(): void
    {
        try {
            $this->getCustomers->getList('2000-01-01', 1, 11);
            $this->fail('Expected validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(400, $exception->getCode());
            $this->assertSame('pageSize', $exception->getDetails()[0]['fieldName']);
            $this->assertSame('11', $exception->getDetails()[0]['fieldValue']);
        }
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetListRejectsReversedDateRange(): void
    {
        try {
            $this->getCustomers->getList('2026-08-31,2026-08-01', 1, 1);
            $this->fail('Expected validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(400, $exception->getCode());
            $this->assertSame('dateRange', $exception->getDetails()[0]['fieldName']);
            $this->assertSame('2026-08-31,2026-08-01', $exception->getDetails()[0]['fieldValue']);
        }
    }
}
