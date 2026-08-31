<?php

/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace AthosCommerce\Feed\Helper;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use AthosCommerce\Feed\Api\Data\CustomersDataInterface;
use AthosCommerce\Feed\Api\Data\CustomersDataInterfaceFactory;
use Magento\Framework\App\Helper\AbstractHelper;

class Customer extends AbstractHelper
{
    protected $customerFactory;
    protected $customersDataFactory;

    /**
     * @param CollectionFactory $customerFactory
     * @param CustomersDataInterfaceFactory $customersDataFactory
     */
    public function __construct(
        CollectionFactory             $customerFactory,
        CustomersDataInterfaceFactory $customersDataFactory
    )
    {
        $this->customerFactory = $customerFactory;
        $this->customersDataFactory = $customersDataFactory;
    }

    /**
     * @param string $dateRangeStr
     * @param int $currentPage
     * @param int $pageSize
     *
     * @return CustomersDataInterface[]
     */
    public function getCustomers(
        string $dateRangeStr,
        int $currentPage,
        int $pageSize
    ): array
    {
        $result = [];
        $customerCollection = $this->customerFactory->create();

        $this->applyDateRangeFilter($customerCollection, $dateRangeStr);
        $this->applyBillingPhoneJoin($customerCollection);
        $customerCollection->getSelect()->order('e.entity_id ASC');
        $customerCollection->setCurPage($currentPage);
        $customerCollection->setPageSize($pageSize);

        $items = $customerCollection->getItems(); // Make query
        foreach ($items as $item) {
            $customersData = $this->customersDataFactory->create();
            $email = (string)$item->getEmail();
            $phoneNumber = (string)($item->getData('billing_telephone') ?? '');

            $customersData->setId($item->getId());
            $customersData->setEmail($email);
            $customersData->setPhoneNumber($phoneNumber);

            $result[] = $customersData;
        }

        return $result;
    }

    /**
     * @param string $dateRangeStr
     * @return int
     */
    public function getCustomersTotalCount(string $dateRangeStr): int
    {
        $customerCollection = $this->customerFactory->create();
        $this->applyDateRangeFilter($customerCollection, $dateRangeStr);
        $customerCollection->getSelect()->order('e.entity_id ASC');

        return (int)$customerCollection->getSize();
    }

    /**
     * @param Collection $customerCollection
     * @param string $dateRangeStr
     * @return void
     */
    private function applyDateRangeFilter(
        Collection $customerCollection,
        string     $dateRangeStr
    ): void
    {
        $dateRange = Utils::getDateRange($dateRangeStr);
        if (!$dateRange) {
            return;
        }

        $select = $customerCollection->getSelect();
        $customerCollection->addBindParam(':from', $dateRange[0]);
        $condition = '(e.created_at >= :from OR e.updated_at >= :from)';

        if (isset($dateRange[1])) {
            $plusOneDay = Utils::plusOneDay($dateRange[1], 'Y-m-d');
            $customerCollection->addBindParam(':to', $plusOneDay);
            $condition = '((e.created_at >= :from AND e.created_at <= :to)'
                . ' OR '
                . '(e.updated_at >= :from AND e.updated_at <= :to))';
        }

        $select->where($condition);
    }

    /**
     * @param Collection $customerCollection
     * @return void
     */
    private function applyBillingPhoneJoin(Collection $customerCollection): void
    {
        $customerCollection->getSelect()->joinLeft(
            ['billing_address' => $customerCollection->getTable('customer_address_entity')],
            'billing_address.entity_id = e.default_billing',
            ['billing_telephone' => 'telephone']
        );
    }
}
