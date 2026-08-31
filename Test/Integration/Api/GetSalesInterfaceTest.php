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

use AthosCommerce\Feed\Api\GetSalesInterface;
use AthosCommerce\Feed\Exception\ValidationException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class GetSalesInterfaceTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var GetSalesInterface
     */
    private $getSales;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->getSales = $this->objectManager->get(GetSalesInterface::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetListRejectsAllDateRange(): void
    {
        try {
            $this->getSales->getList('All', '1,10');
            $this->fail('Expected validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(400, $exception->getCode());
            $this->assertSame('dateRange', $exception->getDetails()[0]['fieldName']);
            $this->assertSame('All', $exception->getDetails()[0]['fieldValue']);
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store athoscommerce/indexing/api_sales_max_page_size 10
     */
    public function testGetListRejectsRowRangeOverConfiguredMaximum(): void
    {
        try {
            $this->getSales->getList('2026-08-01', '1,11');
            $this->fail('Expected validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(400, $exception->getCode());
            $this->assertSame('rowRange', $exception->getDetails()[0]['fieldName']);
            $this->assertSame('1,11', $exception->getDetails()[0]['fieldValue']);
        }
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetListRejectsMalformedRowRangeWithoutParsingNotices(): void
    {
        try {
            $this->getSales->getList('2026-08-01', '1');
            $this->fail('Expected validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(400, $exception->getCode());
            $this->assertSame('rowRange', $exception->getDetails()[0]['fieldName']);
            $this->assertSame('1', $exception->getDetails()[0]['fieldValue']);
        }
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetListRejectsReversedDateRange(): void
    {
        try {
            $this->getSales->getList('2026-08-31,2026-08-01', '1,10');
            $this->fail('Expected validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(400, $exception->getCode());
            $this->assertSame('dateRange', $exception->getDetails()[0]['fieldName']);
            $this->assertSame('2026-08-31,2026-08-01', $exception->getDetails()[0]['fieldValue']);
        }
    }
}
