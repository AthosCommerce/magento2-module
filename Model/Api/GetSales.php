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
use AthosCommerce\Feed\Model\Config;
use AthosCommerce\Feed\Helper\Utils;

class GetSales implements GetSalesInterface
{
    /** @var Sale */
    private $helper;

    /** @var SalesInterfaceFactory */
    private $salesFactory;

    /** @var Config */
    private $config;

    /**
     * @param Sale $helper
     * @param SalesInterfaceFactory $salesFactory
     * @param Config $config
     */
    public function __construct(Sale $helper, SalesInterfaceFactory $salesFactory, Config $config)
    {
        $this->helper = $helper;
        $this->salesFactory = $salesFactory;
        $this->config = $config;
    }

    /**
     * @param string $dateRange
     * @param string $rowRange
     *
     * @return SalesInterface
     *
     * @throws ValidationException
     */
    public function getList(string $dateRange, string $rowRange): SalesInterface
    {
        $maxPageSize = $this->config->getSalesApiMaxPageSizeByStoreId();
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

        $totalCount = $this->helper->getSalesTotalCount($dateRange);
        $offset = (int)$rowRangeValues[0];
        $pageSize = (int)$rowRangeValues[1];
        $sales = $this->salesFactory->create();
        $items = $offset < $totalCount
            ? $this->helper->getSales($dateRange, $rowRange)
            : [];
        $sales->setSales($items);
        $sales->setPageSize($pageSize);
        $sales->setCurrentSize(count($items));
        $sales->setTotalCount($totalCount);

        return $sales;
    }
}
