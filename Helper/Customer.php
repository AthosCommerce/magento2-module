<?php
/**
 * Helper to fetch customer data.
 *
 * This file is part of AthosCommerce/Feed.
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace AthosCommerce\Feed\Helper;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use AthosCommerce\Feed\Api\Data\CustomersDataInterface;
use AthosCommerce\Feed\Api\Data\CustomersDataInterfaceFactory;
use Magento\Framework\App\Helper\AbstractHelper;

class Customer extends AbstractHelper
{
    protected $customerFactory;
    protected $customersDataFactory;

    public function __construct(CollectionFactory $customerFactory, CustomersDataInterfaceFactory $customersDataFactory)
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

        // Build date range query.
        $dateRange = Utils::getDateRange($dateRangeStr);
        if ($dateRange) {
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
            try {
                $phoneNumber = $item->getPrimaryBillingAddress()->getTelephone();
            } catch (\Error $e) {
                // No phone number carry on
            }
            $customersData->setPhoneNumber($phoneNumber);

            $result[] = $customersData;
        }

        return $result;
    }
}
