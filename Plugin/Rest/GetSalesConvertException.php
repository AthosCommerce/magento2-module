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

namespace AthosCommerce\Feed\Plugin\Rest;

use AthosCommerce\Feed\Api\Data\SalesInterface;
use AthosCommerce\Feed\Api\GetSalesInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Webapi\ExceptionConverterInterface;
use Magento\Framework\Webapi\Exception;
use Throwable;

class GetSalesConvertException
{
    /**
     * @var ExceptionConverterInterface
     */
    private $exceptionConverter;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    public function __construct(
        ExceptionConverterInterface $exceptionConverter,
        AthosCommerceLogger $logger
    ) {
        $this->exceptionConverter = $exceptionConverter;
        $this->logger = $logger;
    }

    /**
     * @throws Exception
     */
    public function aroundGetList(
        GetSalesInterface $subject,
        callable $proceed,
        string $dateRange,
        string $rowRange
    ): SalesInterface {
        try {
            return $proceed($dateRange, $rowRange);
        } catch (Throwable $exception) {
            $this->logger->warning($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            throw $this->exceptionConverter->convert($exception);
        }
    }
}
