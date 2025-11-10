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

namespace AthosCommerce\Feed\Model\Api;

use AthosCommerce\Feed\Api\GetSalesInterface;
use AthosCommerce\Feed\Api\Data\SalesInterface;
use AthosCommerce\Feed\Api\Data\SalesInterfaceFactory;
use AthosCommerce\Feed\Exception\ValidationException;
use AthosCommerce\Feed\Helper\Sale;
use AthosCommerce\Feed\Helper\Utils;

class GetSales implements GetSalesInterface
{
    /** @var Sale */
    private $helper;

    /** @var SalesInterfaceFactory */
    private $salesFactory;

    /**
     * @param Sale $helper
     */
    public function __construct(Sale $helper, SalesInterfaceFactory $salesFactory)
    {
        $this->helper = $helper;
        $this->salesFactory = $salesFactory;
    }

    /**
     * @param string $dateRange
     * @param string $rowRange
     *
     * @return SalesInterface
     *
     * @throws ValidationException
     */
    public function getList(string $dateRange = "All", string $rowRange = "All"): SalesInterface
    {
        $errors = [];
        if (!Utils::validateDateRange($dateRange)) {
            $errors[] = "Invalid date range $dateRange";
        }

        if (!Utils::validateRowRange($rowRange)) {
            $errors[] = "Invalid row range $rowRange";
        }

        if (!empty($errors)) {
            throw new ValidationException($errors, 400);
        }

        $sales = $this->salesFactory->create();
        $sales->setSales($this->helper->getSales($dateRange, $rowRange));

        return $sales;
    }
}
