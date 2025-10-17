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

namespace AthosCommerce\Feed\Test\Integration\Model\Task\Validator;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Model\Task\Validator\CustomerValidator;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerValidatorTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var CustomerValidator
     */
    private $customerValidator;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerValidator = $this->objectManager->get(CustomerValidator::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/customer.php
     *
     * @return void
     */
    public function testValidate() : void
    {
        $payload = ['customerId' => 1];
        $result = $this->customerValidator->validate($payload);
        $this->assertEmpty($result->getErrors());
        $this->assertTrue($result->isValid());
    }

    /**
     * @magentoAppIsolation enabled
     *
     * @return void
     */
    public function testValidateEmptyCustomer() : void
    {
        $result = $this->customerValidator->validate([]);
        $this->assertEmpty($result->getErrors());
        $this->assertTrue($result->isValid());
    }

    /**
     * @magentoAppIsolation enabled
     *
     * @return void
     */
    public function testValidateNotExistedCustomer() : void
    {
        $payload = ['customerId' => 100000];
        $result = $this->customerValidator->validate($payload);
        $this->assertNotEmpty($result->getErrors());
        $this->assertFalse($result->isValid());
    }
}
