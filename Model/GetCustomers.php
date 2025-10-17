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

namespace AthosCommerce\Feed\Model;

use AthosCommerce\Feed\Api\GetCustomersInterface;
use AthosCommerce\Feed\Api\Data\CustomersInterface;
use AthosCommerce\Feed\Api\Data\CustomersInterfaceFactory;
use AthosCommerce\Feed\Exception\ValidationException;
use AthosCommerce\Feed\Helper\Customer;
use AthosCommerce\Feed\Helper\Utils;

class GetCustomers implements GetCustomersInterface
{
    /** @var Customer */
    private $helper;

    /** @var CustomersInterfaceFactory */
    private $customersFactory;

    /**
     * @param Customer $helper
     * @param CustomersInterfaceFactory $customersFactory
     */
    public function __construct(Customer $helper, CustomersInterfaceFactory $customersFactory)
    {
        $this->helper = $helper;
        $this->customersFactory = $customersFactory;
    }

    /**
     * @param string $dateRange
     * @param string $rowRange
     *
     * @return CustomersInterface
     *
     * @throws ValidationException
     */
    public function getList(string $dateRange = "All", string $rowRange = "All"): CustomersInterface
    {
        $errors = [];
        if (!Utils::validateDateRange($dateRange)){
            $errors[] = "Invalid date range $dateRange";
        }

        if (!Utils::validateRowRange($rowRange)){
            $errors[] = "Invalid row range $rowRange";
        }

        if (!empty($errors)){
            throw new ValidationException($errors, 400);
        }

        $customers = $this->customersFactory->create();
        $customers->setCustomers($this->helper->getCustomers($dateRange, $rowRange));

        return $customers;
    }
}
