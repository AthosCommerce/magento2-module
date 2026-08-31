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

declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Api;

use AthosCommerce\Feed\Api\GetCustomersInterface;
use AthosCommerce\Feed\Api\Data\CustomersInterface;
use AthosCommerce\Feed\Api\Data\CustomersInterfaceFactory;
use AthosCommerce\Feed\Exception\ValidationException;
use AthosCommerce\Feed\Helper\Customer;
use AthosCommerce\Feed\Model\Config;
use AthosCommerce\Feed\Helper\Utils;

class GetCustomers implements GetCustomersInterface
{
    /** @var Customer */
    private $helper;

    /** @var CustomersInterfaceFactory */
    private $customersFactory;

    /** @var Config */
    private $config;

    /**
     * @param Customer $helper
     * @param CustomersInterfaceFactory $customersFactory
     * @param Config $config
     */
    public function __construct(
        Customer $helper,
        CustomersInterfaceFactory $customersFactory,
        Config $config
    ) {
        $this->helper = $helper;
        $this->customersFactory = $customersFactory;
        $this->config = $config;
    }

    /**
     * @param string $dateRange
     * @param string $rowRange
     *
     * @return CustomersInterface
     *
     * @throws ValidationException
     */
    public function getList(string $dateRange, string $rowRange): CustomersInterface
    {
        $maxPageSize = $this->config->getCustomersApiMaxPageSizeByStoreId();
        $rowRangeValues = Utils::getRowRange($rowRange);
        $errors = [];
        $messages = [];

        if ($dateRange === 'All' || !Utils::validateDateRange($dateRange)) {
            $messages[] = 'Invalid dateRange.';
            $errors[] = [
                'fieldName' => 'dateRange',
                'fieldValue' => $dateRange,
                'message' => 'dateRange must be a bounded date or date range in Y-m-d or Y-m-d,Y-m-d format.'
            ];
        }

        if ($rowRange === 'All' || !Utils::validateRowRange($rowRange)) {
            $messages[] = 'Invalid rowRange.';
            $errors[] = [
                'fieldName' => 'rowRange',
                'fieldValue' => $rowRange,
                'message' => 'rowRange must be a bounded range in start,count format with positive integers.'
            ];
        } elseif ((int)$rowRangeValues[1] > $maxPageSize) {
            $messages[] = 'Invalid rowRange.';
            $errors[] = [
                'fieldName' => 'rowRange',
                'fieldValue' => $rowRange,
                'message' => 'rowRange count exceeds the configured maximum of ' . $maxPageSize . '.'
            ];
        }

        if (!empty($errors)) {
            throw new ValidationException($messages, 400, null, $errors);
        }

        $totalCount = $this->helper->getCustomersTotalCount($dateRange);
        $offset = (int)$rowRangeValues[0];
        $pageSize = (int)$rowRangeValues[1];
        $customers = $this->customersFactory->create();
        $customerItems = $offset < $totalCount
            ? $this->helper->getCustomers($dateRange, $rowRange)
            : [];
        $customers->setCustomers($customerItems);
        $customers->setPageSize($pageSize);
        $customers->setCurrentSize(count($customerItems));
        $customers->setTotalCount($totalCount);

        return $customers;
    }
}
