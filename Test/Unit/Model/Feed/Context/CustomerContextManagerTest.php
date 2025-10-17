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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\Context;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\Context\CustomerContextManager;

class CustomerContextManagerTest extends \PHPUnit\Framework\TestCase
{
    private $sessionMock;

    private $customerRepositoryMock;

    private $customerContextManager;

    public function setUp(): void
    {
        $this->sessionMock = $this->createMock(Session::class);
        $this->customerRepositoryMock = $this->createMock(CustomerRepositoryInterface::class);
        $this->customerContextManager = new CustomerContextManager(
            $this->sessionMock,
            $this->customerRepositoryMock
        );
    }

    public function testSetContextFromSpecification()
    {
        $customerMock = $this->getMockForAbstractClass(CustomerInterface::class);
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn(1);

        $this->customerRepositoryMock->expects($this->once())
            ->method('getById')
            ->with(1)
            ->willReturn($customerMock);
        $this->sessionMock->expects($this->once())
            ->method('setCustomerDataAsLoggedIn')
            ->with($customerMock)
            ->willReturnSelf();

        $this->customerContextManager->setContextFromSpecification($feedSpecificationMock);
    }

    public function testSetContextFromSpecificationNoCustomerId()
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn(null);

        $this->customerContextManager->setContextFromSpecification($feedSpecificationMock);
    }

    public function testResetContext()
    {
        $this->sessionMock->expects($this->once())
            ->method('logout')
            ->willReturnSelf();

        $this->customerContextManager->resetContext();
    }
}
