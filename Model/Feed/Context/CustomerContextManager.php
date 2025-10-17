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

namespace AthosCommerce\Feed\Model\Feed\Context;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;

class CustomerContextManager implements ContextManagerInterface
{
    /**
     * @var Session
     */
    private $session;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * CustomerContextManager constructor.
     * @param Session $session
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Session $session,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->session = $session;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function setContextFromSpecification(FeedSpecificationInterface $feedSpecification): void
    {
        $customerId = $feedSpecification->getCustomerId();
        if (!$customerId) {
            return;
        }

        $customer = $this->customerRepository->getById((int) $customerId);
        $this->session->setCustomerDataAsLoggedIn($customer);
    }

    /**
     *
     */
    public function resetContext(): void
    {
        $this->session->logout();
    }
}
