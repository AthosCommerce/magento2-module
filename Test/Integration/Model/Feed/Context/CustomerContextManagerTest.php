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

namespace AthosCommerce\Feed\Test\Integration\Model\Feed\Context;

use Magento\Customer\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Model\Feed\Context\CustomerContextManager;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerContextManagerTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var CustomerContextManager
     */
    private $customerContextManager;
    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;
    /**
     * @var Session
     */
    private $session;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerContextManager = $this->objectManager->get(CustomerContextManager::class);
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->session = $this->objectManager->get(Session::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/customer.php
     */
    public function testSetContextFromSpecification() : void
    {
        $specification = $this->specificationBuilder->build(['customerId' => 1]);
        $this->customerContextManager->setContextFromSpecification($specification);
        $this->assertTrue($this->session->isLoggedIn());
        $this->assertEquals(1, $this->session->getCustomerId());
        $this->customerContextManager->resetContext();
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testSetContextFromSpecificationWithEmptyCustomerId() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->customerContextManager->setContextFromSpecification($specification);
        $this->assertFalse($this->session->isLoggedIn());
        $this->assertNull($this->session->getCustomerId());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/customer.php
     */
    public function testSetContextFromSpecificationWithInvalidCustomerId() : void
    {
        $specification = $this->specificationBuilder->build(['customerId' => 200]);
        $this->expectException(NoSuchEntityException::class);
        $this->customerContextManager->setContextFromSpecification($specification);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/customer.php
     */
    public function testResetContext() : void
    {
        $specification = $this->specificationBuilder->build(['customerId' => 1]);
        $this->customerContextManager->setContextFromSpecification($specification);
        $this->assertTrue($this->session->isLoggedIn());
        $this->assertEquals(1, $this->session->getCustomerId());
        $this->customerContextManager->resetContext();
        $this->assertFalse($this->session->isLoggedIn());
        $this->assertNull($this->session->getCustomerId());
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testResetContextWithEmptyCustomer() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $this->customerContextManager->setContextFromSpecification($specification);
        $this->assertFalse($this->session->isLoggedIn());
        $this->assertNull($this->session->getCustomerId());
        $this->customerContextManager->resetContext();
        $this->assertFalse($this->session->isLoggedIn());
        $this->assertNull($this->session->getCustomerId());
    }
}
