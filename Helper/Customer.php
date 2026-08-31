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
    public const MAX_PAGE_SIZE = 500;

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
     * @param string $rowRangeStr
     *
     * @return CustomersDataInterface[]
     */
    public function getCustomers(string $dateRangeStr, string $rowRangeStr): array
    {
        $result = [];
        $customerCollection = $this->customerFactory->create();

        $select = $customerCollection->getSelect();

        $this->applyDateRangeFilter($customerCollection, $dateRangeStr);

        // Chunk customers with row range.
        $rowRange = Utils::getRowRange($rowRangeStr);
        if (isset($rowRange[0]) && isset($rowRange[1])) {
            $select->limit((int)$rowRange[1], (int)$rowRange[0]);
        }

        $items = $customerCollection->getItems(); // Make query
        foreach ($items as $item) {
            $customersData = $this->customersDataFactory->create();

            $customersData->setId($item->getId());
            $customersData->setEmail($item->getEmail());
            $phoneNumber = '';
            $billingAddress = $item->getPrimaryBillingAddress();
            if ($billingAddress) {
                $phoneNumber = (string)$billingAddress->getTelephone();
            }
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
            $condition = <<<SQL
                (
                    (e.created_at >= :from AND e.created_at <= :to)
                    OR
                    (e.updated_at >= :from AND e.updated_at <= :to)
                )
            SQL;
        }

        $select->where($condition);
    }
}
